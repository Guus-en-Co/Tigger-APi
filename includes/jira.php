<?php

include 'classes.php';

class Jira
{
    /**
     * The array containing all projects
     *
     * @var array.
     */
    private $projects = array();
    private $rawProjects = array();
    private $projectBoards = array(); // Do we use all those variables???
    private $selectedProjects = array();
    private $basicAuth = '';
    private $urlPrefix;
    private $startAt = 0;
    private $board = 0;
    private $boardInfo;

    /**
     * Constructor
     *
     * @param string $urlPrefix The website url prefix, if empty default host wil be used.
     */
    public function __construct($config)
    {
        $this->urlPrefix = $config->jira_url;
        if(isset($config->board)) {
            $this->board = $config->board;
        }
        if(isset($config->projects)) {
            $this->projectBoards = $config->projects;
        }
        if(isset($config->basicAuth)) {
            $this->basicAuth = $config->basicAuth;
            // If this is not set chances are the script wont do much
        }
    }

    public function checkPassword($username, $password){
        $this->basicAuth = base64_encode($username.":".$password);

        $response = $this->doGet('/rest/auth/1/session');

        if(is_null($response)){
            return false;
        } else {
            return true;
        }
    }

    /**
     * Returns an array containing all project & sprint information
     *
     * @param string $this->board Set in the constructor, dictates what boards will be used
     */
    public function getSelectedProjects()
    {
        foreach($this->projectBoards as &$project){
            $this->selectedProjects[] = $this->getBoard($project);
        }
        return $this->selectedProjects;
    }

    /**
     * gets a board and sprints / issues
     * !WARNING! Will only get the first 50 sprints!
     *
     * @param string $this->board Set in the constructor, dictates what board will be used
     */
    public function getBoard($board = null) 
    {
        $boardSprints = $this->doGet('rest/agile/1.0/board/' . $board->board_id . '/sprint');
        $board->activeSprints = array();
        foreach($boardSprints->values as &$sprint){
            if($sprint->state == 'active'){
                $newSprint = new Sprint($sprint->id, $sprint->name, $sprint->state, $sprint->startDate, $sprint->endDate);
                $issues = $this->doGet('rest/agile/1.0/board/' . $board->board_id . '/sprint/' . $sprint->id . '/issue');
                $newSprint->issuesCount = count($issues->issues);
                foreach($issues->issues as &$issue){
                    $timetracking = new stdClass();

                    if ($issue->fields->timetracking) {
                        if(isset($issue->fields->timetracking->originalEstimateSeconds) && isset($issue->fields->timetracking->remainingEstimateSeconds)){
                            $timetracking->originalEstimateSeconds = $issue->fields->timetracking->originalEstimateSeconds;
                            $timetracking->remainingEstimateSeconds = $issue->fields->timetracking->remainingEstimateSeconds;
                            $newSprint->originalEstimateSeconds += $timetracking->originalEstimateSeconds;
                            $newSprint->remainingEstimateSeconds += $timetracking->remainingEstimateSeconds;
                        }
                    }

                    $newSprint->addIssue($issue, $timetracking);
                    if($issue->fields->status->statusCategory->id == 3){ 
                        $newSprint->doneCount++;
                    } else if($issue->fields->status->statusCategory->id == 4){
                        $newSprint->inProgressCount++;
                    }
                }
                $board->activeSprints[] = $newSprint;
            }
        }
        return $board;
    }

    /**
     * Get all projects, if no projects where found then refresh list
     *
     * @param boolean $refresh Boolean to tell the function to force update or not.
     */
    public function getProjects($alsoGetAllIssues = false, $refresh = false)
    {
        if (empty($this->projects) || $refresh) {
            $this->getAllProjects($alsoGetAllIssues);
        }
        return $this->projects;
    }

    /**
     * Get all projects as boards, and sort issues by normal/sprint/epic
     *
     */
    private function getAllProjects($alsoGetAllIssues)
    {
        $getResponse = $this->doGet('rest/agile/1.0/board/', $this->startAt);
        $total = $getResponse->total;
        if ($total >= ($this->startAt)) {
            $this->startAt += 50;
            $this->rawProjects = array_merge($this->rawProjects, $getResponse->values);
            $this->getAllProjects($alsoGetAllIssues);
        } else {
            if(!$alsoGetAllIssues){
                foreach ($this->rawProjects as &$project) {
                    $tmpProject = new stdClass();
                    $tmpProject->board_id = $project->id;
                    $tmpProject->board_name = $project->name;
                    $this->projects[] = $tmpProject;
                }
            } else {
                $this->getAllIssues($this->rawProjects);
            }
        }
    }

    private function getAllIssues($issueList)
    {
        foreach ($issueList as &$project) {
            $tmpProject = new Project($project->id, $project->name);
            $tmpProject->sprints = $tmpProject->epics = $tmpProject->issues = array();

            // Get all issues that belong to the board, and sort them by normal/sprint/epic
            /* $projectIssues = doGet('rest/agile/1.0/board/' . $project->id . '/issue');
            foreach ($projectIssues->issues as &$projectIssue) {
                if (isset($projectIssue->fields->epic)) {
                    $tmpProject->addEpic($projectIssue);
                } elseif (isset($projectIssue->fields->sprint)) {
                    $tmpProject->addSprint($projectIssue);
                } else {
                    $tmpProject->addIssue($projectIssue);
                }
            } */
        
            // Add temporary project list with projects
            
            $this->projects[] = $tmpProject;
        }
    }

    /**
     * Get data from get request to jira, given default user login
     *
     * @param string $url The website url to make a call to.
     * @param int $startAt Is the start at value for jira.
     * @param int $maxResults Is the maximum results given by Jira. 
     */
    function doGet($url, $startAt = 0, $maxResults = 500)
    {
        if (!$url || $url == '') {
            return;
        }

        if (substr($url, 0, 4) != "http") {
            if (substr($this->urlPrefix, -1) != '/') {
                $this->urlPrefix = $this->urlPrefix . '/';
            }
            $url = $this->urlPrefix . '' . $url;
            
        }

        if (!strpos($url, 'maxResults')) {
            $url .= '?startAt='.$startAt.'&maxResults='.$maxResults;
        }
        
        $options = array(
            CURLOPT_RETURNTRANSFER => true,     // return web page
            CURLOPT_HEADER         => false,    // don't return headers
            CURLOPT_FOLLOWLOCATION => true,     // follow redirects
            CURLOPT_ENCODING       => "",       // handle all encodings
            CURLOPT_USERAGENT      => "spider", // who am i
            CURLOPT_AUTOREFERER    => true,     // set referer on redirect
            CURLOPT_CONNECTTIMEOUT => 120,      // timeout on connect
            CURLOPT_TIMEOUT        => 120,      // timeout on response
            CURLOPT_MAXREDIRS      => 10,       // stop after 10 redirects
        );

        $ch      = curl_init($url);
        curl_setopt_array($ch, $options);
        $headers = array(
            'Content-Type:application/json',
            'Authorization: Basic ' . $this->basicAuth
        );
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        $content = curl_exec($ch);
        $err     = curl_errno($ch);
        $errmsg  = curl_error($ch);
        $header  = curl_getinfo($ch);
        curl_close($ch);

        $header['errno']   = $err;
        $header['errmsg']  = $errmsg;
        $header['content'] = $content;
        // return $header; // For debug
        return json_decode($content);

        // DO NOT FORGET TO ADD cacert.pem AS A CERTIFICATE!
    }

}

