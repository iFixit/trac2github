<?php
require("git_users.php");
require("milestones.php");

class Ticket {
   public static $github;

   private $id;
   private $attr;

   public static function loadFromTrac($id) {
      $q_select = "SELECT * FROM `ticket` WHERE `id` = $id";
      $result = Trac::queryRow($q_select);
      return $result ? new self($result) : null;
   }

   public function __construct($dbAttributes) {
      $this->attr = $dbAttributes;
      $this->id = $this->attr['id'];
   }

   public function toIssueJson() {
      $json = array(
         'title' => $this->attr['summary'],
         'body' => $this->translateDescription(),
         'assignee' => GitUsers::fromTrac($this->attr['owner']) ?: $this->attr['owner'],
         'milestone' => Milestones::gitId($this->attr['milestone']),
         'labels' => $this->getLabels()
      );

      return $json;
   }

   public function saveToGithub() {
      self::$github->add_issue($this->toIssueJson());
      if ($this->attr['status'] == 'closed') {
         self::$github->update_issue($this->id, array('state' => 'closed'));
      }
   }

   private function translateDescription() {
      // more on this later
      $desc = $this->attr['description'];
      $desc = str_replace("\r\n", "\n", $desc);
      return $dec;
   }

   private function getLabels() {
      $labels = array();
      if ($comp = $this->attr['component'])
         $labels[] = "C-{$comp}";
      if ($resolution = $this->attr['resolution'])
         $labels[] = "R-{$resolution}";
      if (($status = $this->attr['status']) && $status != 'closed' && $status != 'assigned')
         $labels[] = $status;
      if ($priority = $this->attr['priority'])
         $labels[] = "P-{$priority}";

      return $labels;
   }
}
