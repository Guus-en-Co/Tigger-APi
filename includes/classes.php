<?php

class Project
{
    public $id;
    public $name;
    public $sprints;
    public $epics;
    public $issues; // Only issues that are not in a sprint / epic!

    public function __construct($id, $name)
    {
        $this->id = $id;
        $this->name = $name;
    }

    /**
     * Get's issue, checks if epic object already exist in context. If not, create new.
     *
     * @param object $epic Raw issue containing epic information
     */
    public function addEpic($epic)
    {
        $epicExist = false;
        foreach ($this->epics as &$tmpEpic) {
            if ($tmpEpic->id == $epic->fields->epic->id) {
                $epicExist = true;
                $tmpEpic->issues[] = getIssue($epic);
                break;
            }
        }

        if (!$epicExist) {
            $tmpEpic = new Epic($epic->fields->epic->id, $epic->fields->epic->key, $epic->fields->epic->name, $epic->fields->epic->summary);
            $tmpEpic->issues[] = getIssue($epic);
            $this->epics[] = $tmpEpic;
        }
    }

    /**
     * Get's issue, checks if sprint object already exist in context. If not, create new.
     *
     * @param object $sprint Raw issue containing sprint information
     */
    public function addSprint($sprint)
    {
        $sprintExist = false;
        foreach ($this->sprints as &$tmpSprint) {
            if ($tmpSprint->id == $sprint->fields->sprint->id) {
                $sprintExist = true;
                $tmpSprint->issues[] = getIssue($sprint);
                break;
            }
        }

        if (!$sprintExist) {
            $tmpSprint = new Sprint($sprint->fields->sprint->id, $sprint->fields->sprint->name, $sprint->fields->sprint->state, $sprint->fields->sprint->startDate, $sprint->fields->sprint->endDate);
            $tmpSprint->issues[] = getIssue($sprint);
            $this->sprints[] = $tmpSprint;
        }
    }

    /**
     * Get's issue. Adds issue to array list contains more issues.
     *
     * @param object $issue Raw issue
     */
    public function addIssue($issue)
    {
        $this->issues[] = getIssue($issue);
    }
}

class Epic
{
    public $id;
    public $key;
    public $name;
    public $summary;
    public $issues;

    public function __construct($id, $key, $name, $summary)
    {
        $this->id = $id;
        $this->key = $key;
        $this->name = $name;
        $this->summary = $summary;
    }
}

class Sprint
{
    public $id;
    public $name;
    public $state;
    public $startDate;
    public $endDate;
    public $issues = array();
    public $issuesCount = 0;
    public $doneCount = 0;
    public $inProgressCount = 0;
    public $originalEstimateSeconds = 0;
    public $remainingEstimateSeconds = 0;

    public function __construct($id, $name, $state, $startDate, $endDate)
    {
        $this->id = $id;
        $this->name = $name;
        $this->state = $state;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function addIssue($issue, $timetracking)
    {
        $issue = getIssue($issue);
        if($timetracking && isset($timetracking->originalEstimateSeconds) && isset($timetracking->remainingEstimateSeconds)){
            $issue->originalEstimateSeconds = $timetracking->originalEstimateSeconds;
            $issue->remainingEstimateSeconds = $timetracking->remainingEstimateSeconds;
        }
        $this->issues[] = $issue;
    }
}

class Issue
{
    public $id;
    public $key;
    public $summary;
    /**
     * 1 = undefined    2 = todo    3 = done    4 = in progress
     * 1 = 0%           2 = 0%      3 = 100%    4 = 0%
     * How can I know it it is in testing / in review?? (For now it's not important and won't be implemented)
     */
    public $statusId;
    public $originalEstimateSeconds;
    public $remainingEstimateSeconds;
}

/**
 * Returns all usable data from raw issue and return issue
 *
 * @param object $rawIssue Object that contains raw issue information.
 */
function getIssue($rawIssue)
{
    $issue = new Issue();
    
    $issue->id = $rawIssue->id;
    $issue->key = $rawIssue->key;
    $issue->summary = $rawIssue->fields->summary;
    $issue->statusId = $rawIssue->fields->status->statusCategory->id;
    return $issue;
}
