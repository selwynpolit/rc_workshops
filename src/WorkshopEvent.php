<?php

namespace Drupal\workshops;

use Drupal\node\Entity\Node;

/**
 * Class WorkshopEvent.
 *
 * @package Drupal\workshops\selwyn
 */
class WorkshopEvent {

  private $startDate;
  private $endDate;
  private $locationString;
  private $country;
  private $title;
  private $leaders;
  private $audience;
  private $originalArray;
  private $type;

  /**
   * WorkshopEvent constructor.
   *
   * @param array $ws
   *   Array of strings from email.
   * @param string $wsType
   *   Type of workshop: proposed or scheduled.
   */
  public function __construct(array $ws, $wsType) {
    $this->originalArray = $ws;
    $this->buildDates($ws[0]);
    $this->type = $wsType;

    // 6 liners are arranged differently.
    if (count($ws) == 6) {
      // Munge 1, 2, 3 until I can get smarter.
      $this->buildLocation($ws[1] . ' ' . $ws[2]);
      $this->buildCountry($ws[1] . ' ' . $ws[2]);
      $this->buildTitle($ws[1] . ' ' . $ws[2] . ' ' . $ws[3]);
      $this->buildLeader($ws[4]);
      $this->buildAudience($ws[5]);

    }
    else {
      // 5 and 4 liners.
      $this->buildLocation($ws[1]);
      $this->buildCountry($ws[1]);
      $this->buildTitle($ws[2]);
      $this->buildLeader($ws[3]);
      if (array_key_exists(4, $ws)) {
        $this->buildAudience($ws[4]);
      }
    }
  }

  /**
   * Return Node-ready array for storage.
   *
   * @param array $node_data
   *   Array for storage to node in Node::create().
   *
   * @return bool
   *   Success or Failure.
   */
  public function getNodeReady(array &$node_data) {

    if (!is_a($this->startDate, 'DateTime')) {
      devel_set_message("invalid start date for " . $this->title, 'error');
      return FALSE;
    }
    if (!is_a($this->endDate, 'DateTime')) {
      devel_set_message("invalid end date for " . $this->title, 'error');
      return FALSE;
    }

    $node_data = [
      'type' => 'workshop',
      'title' => $this->title,
      'field_workshop_type' => $this->type,
      'field_workshop_location' => $this->locationString,
      'field_workshop_leader_ref' => $this->leaders,
      'field_workshop_audience' => $this->audience,
      'field_workshop_country' => $this->country,
      'field_workshop_start_date' => $this->startDate->format('Y-m-d'),
      'field_workshop_end_date' => $this->endDate->format('Y-m-d'),
      'field_workshop_original_posting' => implode(chr(13), $this->originalArray),
    ];
    return TRUE;
  }

  /**
   * Build Country.
   *
   * @param string $str
   *   Country Line.
   */
  public function buildCountry($str) {
    if (strlen(stristr($str, "USA")) > 0) {
      $this->country = "USA";
    }
    elseif (strlen(stristr($str, "North America")) > 0) {
      $this->country = "USA";
    }
    elseif (strlen(stristr($str, "England")) > 0) {
      $this->country = "England";
    }
    elseif (strlen(stristr($str, "Netherlands")) > 0) {
      $this->country = "The Netherlands";
    }
    elseif (strlen(stristr($str, "Australia")) > 0) {
      $this->country = "Australia";
    }
    elseif (strlen(stristr($str, "Switzerland")) > 0) {
      $this->country = "Switzerland";
    }
    elseif (strlen(stristr($str, "Taiwan")) > 0) {
      $this->country = "Taiwan";
    }
    elseif (strlen(stristr($str, "Denmark")) > 0) {
      $this->country = "Denmark";
    }
    elseif (strlen(stristr($str, "Swaziland")) > 0) {
      $this->country = "Swaziland";
    }
    elseif (strlen(stristr($str, "Poland")) > 0) {
      $this->country = "Poland";
    }
    else {
      $this->country = NULL;
    }
  }

  /**
   * Return title.
   *
   * @return mixed
   *   Title.
   */
  public function getTitle() {
    return $this->title;
  }

  /**
   * Build location String.
   *
   * @param string $str
   *   Location string.
   */
  public function buildLocation(string $str) {
    $this->locationString = $str;
  }

  /**
   * Build Title.
   *
   * @param string $str
   *   Title string.
   */
  public function buildTitle(string $str) {
    $this->title = $str;
  }

  /**
   * Build Leaders array from a string that could have up to 3 leaders in it.
   *
   * @param string $leaderString
   *   Leader string.
   */
  public function buildLeader(string $leaderString) {
//    $this->leader = $str;
    $leaders = null;
    if (strlen($leaderString)) {
      $leaders = str_replace("assisted by", "&", $leaderString);
      $leaders = str_replace(", ", "&", $leaders);
      $leaders = str_replace("&& ", "&", $leaders);
      $leaders = explode('&', $leaders);
      $leaders = array_map('trim', $leaders);
      $leaders = array_map('rtrim', $leaders);
    }
    $this->leaders = $leaders;
  }

  /**
   * Build audience.
   *
   * @param string $str
   *   Audience string.
   */
  public function buildAudience(string $str) {
    $this->audience = $str;
  }

  /**
   * All the regex date magic happens here.
   *
   * @param string $str
   *   The date line.
   */
  public function buildDates($str) {
    // Grab the month word.
    $rc = preg_match("/^\w+/", trim($str), $output_array);
    if ($rc) {
      $month = $output_array[0];
    }
    // First part of date i.e. Feb 16-18 - should return the 16.
    $rc = preg_match("/\d+/", $str, $output_array);
    if ($rc) {
      $first_day = $output_array[0];
    }
    $rc = preg_match("/(\d+)(\/)/", $str, $output_array);
    if ($rc) {
      $second_day = $output_array[1];
    }
    // Sometimes there's a second month.  e.g. August 31-September 3/18.
    $second_month = "";
    $rc = preg_match("/(\w+) (\d+)(\/)/", $str, $output_array);
    if ($rc) {
      // Is this a valid month?
      $x = \DateTime::createFromFormat('M', $output_array[1]);
      if ($x) {
        $second_month = $output_array[1];
      }
    }

    $rc = preg_match("/(\/)(\d+)/", $str, $output_array);
    if ($rc) {
      $year = "20" . $output_array[2];
    }

    $date_str = $first_day . '-' . $month . '-' . $year . '00:00:00';
    $this->startDate = \DateTime::createFromFormat('j-M-Y H:i:s', $date_str);

    $month = (!empty($second_month) ? $second_month : $month);
    $date_str = $second_day . '-' . $month . '-' . $year . '00:00:00';
    $this->endDate = \DateTime::createFromFormat('j-M-Y H:i:s', $date_str);

  }

  public function processLeaders() {
    // Loop thru the leaders and look them up in the drupal db
    // If not there, look them up on the rc.org website and get the confidence level
    // store them in the drupal db as workshop leader content
    // Update the $wsData array with the nids of the leaders... will that work?

    for ($i=0;$i<count($this->leaders);$i++) {
      // Lookup in drupal to see if it exists and grab the nid
      $query = \Drupal::entityQuery('node');

      // Some strings need slashes before special characters ie. double quotes.
      $leader = $this->convertSmartQuotes($this->leaders[$i]);
      $leader = addslashes($leader);

      $query->condition('title', '%'.$leader.'%', 'LIKE')
        ->condition('type', 'workshop_leader');
      $nids = $query->execute();

      if ($nids) {
        $nid = current($nids);
        $this->leaders[$i] = $nid;
        continue;
      }

      // If none found, go look them up on the rc website
      if (count($nids) === 0) {
        $rc = $this->validateLeader($this->leaders[$i]);
        // If found - write the leader to the db
        if ($rc == TRUE) {
          $leaderNode = Node::create([
            'type' => 'workshop_leader',
            'title' => $this->leaders[$i],
            'field_workshop_leader_confidence' => 4,
          ]);
          $leaderNode->save();
          $nid = $leaderNode->id();
          if ($nid) {
            $this->leaders[$i] = $nid;
          }
        }

      }

    }
  }

  /**
   * Check on the RC website if this is a valid leader.
   *
   * @param string $leaderName
   *   The name of the leader.
   *
   * @return bool
   *   True = leader name is valid.
   */
  private function validateLeader(string $leaderName) {
    if (empty($leaderName)) {
      return false;
    }

    $content_div = "";
    $doc = new \DOMDocument();

    $plusName = str_replace(' ', '+', $leaderName);
    $plusName = '%22' . $plusName . '%22';
    $url = 'https://www.rc.org/page/search?search=' . $plusName;
    $scrape = file_get_contents($url);

    // set error level to avoid silly warnings when loading html from site.
    $internalErrors = libxml_use_internal_errors(true);
    $doc->loadHTML($scrape);
    // Restore error level.
    libxml_use_internal_errors($internalErrors);

    $divs = $doc->getElementsByTagName('div');

    // Loop through the DIVs looking for one with an id of "content".
    /* @var $div \DOMElement */
    foreach($divs as $div) {
      if ($div->getAttribute('id') === 'content') {
        $content_div=$div->nodeValue;
        break;
      }
    }

    // Look in the content div for the leader's name.
    if (strlen(stristr($content_div, $leaderName))>0) {
      return true;
    }
    return false;
  }

  /**
   * Convert smart quotes (including mac quotes) to regular double quotes.
   *
   * You could do this:
   *  $leader = str_replace('”', '"', $leader);
   *  $leader = str_replace('“', '"', $leader);
   *
   */
  private function convertSmartQuotes($string)
  {
    $search = array(
      chr(145),
      chr(146),
      chr(147),
      chr(148),
      chr(151),
      chr(93),
      '“',
      '”',
    );

    $replace = array(
      "'",
      "''",
      '""',
      '""',
      '-',
      '"',
      '"',
      '"',
      );

    return str_replace($search, $replace, $string);
  }


}
