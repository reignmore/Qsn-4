<?php
class Feedback {
  // (A) CONSTRUCTOR - CONNECT TO DATABASE
  private $pdo = null;
  private $stmt = null;
  public $error = "";
  function __construct () {
    try {
      $this->pdo = new PDO(
        "mysql:host=".DB_HOST.";dbname=".DB_NAME.";charset=".DB_CHARSET,
        DB_USER, DB_PASSWORD, [
          PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
          PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC
      ]);
    } catch (Exception $ex) { exit($ex->getMessage()); }
  }

  // (B) DESTRUCTOR - CLOSE DATABASE CONNECTION
  function __destruct () {
    if ($this->stmt!==null) { $this->stmt = null; }
    if ($this->pdo!==null) { $this->pdo = null; }
  }

  // (C) SUPPORT FUNCTION - SQL QUERY
  //  $sql - sql to run
  //  $data - data to bind
  function query ($sql, $data=null) {
    $this->stmt = $this->pdo->prepare($sql);
    $this->stmt->execute($data);
  }

  // (D) SAVE FEEDBACK
  //  $title : feedback title
  //  $questions : array of feedback questions
  //  $desc : feedback description (optional)
  //  $id : feedback id (for update only)
  function save ($title, $questions, $desc=null, $id=null) {
    // (D1) AUTO-COMMIT OFF
    $this->pdo->beginTransaction();

    // (D2) UPDATE/INSERT FEEDBACK
    if ($id==null) {
      $sql = "INSERT INTO `feedback` (`feedback_title`, `feedback_desc`) VALUES (?,?)";
      $data = [$title, $desc];
    } else {
      $sql = "UPDATE `feedback` SET `feedback_title`=?, `feedback_desc`=? WHERE `feedback_id`=?";
      $data = [$title, $desc, $id];
    }
    $this->query($sql, $data);
    if ($id==null) { $id = $this->pdo->lastInsertId(); }

    // (D3) DELETE OLD QUESTIONS
    $this->query(
      "DELETE FROM `feedback_questions` WHERE `feedback_id`=?", [$id]
    );

    // (D4) ADD QUESTIONS
    $sql = "INSERT INTO `feedback_questions` (`feedback_id`, `question_id`, `question_text`, `question_type`) VALUES ";
    $data = [];
    foreach ($questions as $qid=>$q) {
      $sql .= "(?,?,?,?),";
      $data[] = $id; $data[] = $qid + 1;
      $data[] = $q[0]; $data[] = $q[1];
    }
    $sql = substr($sql, 0, -1) . ";";
    $this->query($sql, $data);

    // (D5) COMMIT
    $this->pdo->commit();
    return true;
  }

  // (E) GET FEEDBACK QUESTIONS
  //  $id : feedback id
  //  $user : also include user feedback? default false.
  function get ($id, $user=false) {
    // (E1) GET QUESTIONS
    $this->query(
      "SELECT * FROM `feedback_questions` WHERE `feedback_id`=?", [$id]
    );
    $results = [];
    while ($row = $this->stmt->fetch()) {
      $results[$row["question_id"]] = [
        "question_text" => $row["question_text"],
        "question_type" => $row["question_type"]
      ];
    }

    // (E2) INCLUDE USER FEEDBACK
    if ($user==true) { foreach ($results as $qid=>$q) {
      $sql = "FROM `feedback_users` WHERE `feedback_id`=? AND `question_id`=?";

      // (E2-1) AVERAGE RATING
      if ($q["question_type"]=="R") {
        $this->query("SELECT AVG(`feedback_value`) $sql", [$id, $qid]);
        $results[$qid]["feedback_value"] = $this->stmt->fetchColumn();
      }

      // (E2-2) OPEN FIELD
      else {
        $results[$qid]["feedback_value"] = [];
        $this->query("SELECT `feedback_value` $sql", [$id, $qid]);
        while ($row = $this->stmt->fetch()) {
          $results[$qid]["feedback_value"][] = $row["feedback_value"];
        }
      }
    }}

    // (E3) RESULTS
    return $results;
  }

  // (F) SAVE USER FEEDBACK
  //  $uid : user id
  //  $fid : feedback id
  //  $feed : array of feedback data
  function saveuser ($uid, $fid, $feed) {
    $sql = "REPLACE INTO `feedback_users` (`user_id`, `feedback_id`, `question_id`, `feedback_value`) VALUES ";
    $data = [];
    foreach ($feed as $qid=>$val) {
      $sql .= "(?,?,?,?),";
      $data[] = $uid; $data[] = $fid;
      $data[] = $qid; $data[] = $val;
    }
    $sql = substr($sql, 0, -1) . ";";
    $this->query($sql, $data);
    return true;
  }
}

// (G) DATABASE SETTINGS - CHANGE TO YOUR OWN!
define("DB_HOST", "localhost");
define("DB_NAME", "test");
define("DB_CHARSET", "utf8");
define("DB_USER", "root");
define("DB_PASSWORD", "");

// (H) NEW FEEDBACK OBJECT
$FEED = new Feedback();
