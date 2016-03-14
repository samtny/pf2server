<?php

class LatLon {
	public $lat;
	public $lon;
}

class GeocodeResult {
	public $coordinate;
	public $southwest;
	public $northeast;
	function __construct() {
		$this->coordinate = new LatLon();
		$this->southwest = new LatLon();
		$this->northeast = new LatLon();
	}
}

class ReverseGeocodeResult {
	public $street;
	public $city;
	public $state;
	public $country;
	public $zip;
	public $stateshort;
	public $neighborhood;
}

class Status {
	public $status;
	function __construct() {
		$this->status = "none";
	}
}

class GameDict {
	public $en;
	function __construct() {
		$this->en = array();
	}
}

class Stats {
	public $notifications;
	function __construct() {
		$this->notifications = 0;
	}
}

class Meta {
	public $q;
	public $n;
	public $gamedict;
	public $stats;
	public $message;
	function __construct() {
		$this->gamedict = new GameDict();
	}
}

class Request {
	public $user;
	public $venues;
	function __construct() {
		$this->venues = array();
	}
	public function loadXML($xml) {
		
		$doc = new DOMDocument();
		$doc->loadXML($xml);
		
		$users = $doc->getElementsByTagName("user");
		if ($users->length == 1) {
			
			$u = new User();
			$u->id = $users->item(0)->getAttribute("key");
			
			$tokens = $users->item(0)->getElementsByTagName("token");
			foreach ($tokens as $token) {
				$t = new Token();
				$t->id = $token->getAttribute("key");
				$t->service = $token->getAttribute("service");
				$t->token = $token->nodeValue;
				$u->tokens[] = $t;
			}
			
			$this->user = $u;
			
		}
		
		$locs = $doc->getElementsByTagName("loc");
		
		foreach ($locs as $loc) {
			
			$venue = new Venue();
			
			$venue->id = $loc->getAttribute("key");
			$venue->flag = $loc->getAttribute("flag");
			$venue->name = $loc->getElementsByTagName("name")->item(0)->nodeValue;
			$venue->street = $loc->getElementsByTagName("addr")->item(0)->nodeValue;
			$venue->city = $loc->getElementsByTagName("city")->item(0)->nodeValue;
			$venue->state = $loc->getElementsByTagName("state")->item(0)->nodeValue;
			$venue->zipcode = $loc->getElementsByTagName("zipcode")->item(0)->nodeValue;
			$venue->phone = $loc->getElementsByTagName("phone")->item(0)->nodeValue;
			$venue->lat = $loc->getElementsByTagName("lat")->item(0)->nodeValue;
			$venue->lon = $loc->getElementsByTagName("lon")->item(0)->nodeValue;
			$venue->updated = $loc->getElementsByTagName("date")->item(0)->nodeValue;
			$venue->source = $loc->getElementsByTagName("source")->item(0)->nodeValue;
			$venue->url = $loc->getElementsByTagName("url")->item(0)->nodeValue;
			
			$games = $loc->getElementsByTagName("game");
			
			foreach ($games as $g) {
				
				$game = new Game();
				
				$game->id = $g->getAttribute("key");
				$game->deleted = $g->getAttribute("deleted");
				$game->abbr = $g->getElementsByTagName("abbr")->item(0)->nodeValue;
				$game->cond = $g->getElementsByTagName("cond")->item(0)->nodeValue;
				$game->price = $g->getElementsByTagName("price")->item(0)->nodeValue;
				$game->ipdb = $g->getElementsByTagName("ipdb")->item(0)->nodeValue;
				
				$venue->games[] = $game;
				
			}
			
			$comments = $loc->getElementsByTagName("comment");
			
			foreach ($comments as $c) {
				
				$comment = new Comment();
				
				$comment->id = $c->getAttribute("key");
				$comment->text = $c->getElementsByTagName("ctext")->item(0)->nodeValue;
				$comment->date = $c->getElementsByTagName("cdate")->item(0)->nodeValue;
				
				$venue->comments[] = $comment;
				
			}
			
			$this->venues[] = $venue;
			
		}
		
	}
}

class Result {
	public $status;
	public $meta;
	public $venues;
	function __construct() {
		$this->status = new Status();
		$this->meta = new Meta();
		$this->venues = array();
	}
	public function addVenue($venue) {
		$this->venues[] = $venue;
	}
	public function saveJSON() {
		return json_encode($this);
	}
	public function saveXML($minimal = false) {
		
		// instantiate xml object
		$imp = new DOMImplementation();
		$dtd = $imp->createDocumentType('pinfinderapp', '', 'http://www.pinballfinder.org/pinfinderapp.dtd');
		$doc = $imp->createDocument('', '', $dtd);
		$doc->encoding="UTF-8";
		
		$doc->formatOutput = false;
		
		$r = $doc->createElement('pinfinderapp');
		$doc->appendChild($r);
		
		// start with status;
		$status = $doc->createElement("status");
		$status->appendChild($doc->createTextNode($this->status->status));
		$r->appendChild($status);
		
		// start "meta" block - game dictionary, etc;
		$meta = $doc->createElement("meta");
		
		if ($this->meta->q) {
			$name = $doc->createElement("q");
			$name->appendChild($doc->createTextNode($this->meta->q));
			$meta->appendChild($name);
		}
		
		if ($this->meta->n) {
			$name = $doc->createElement("n");
			$name->appendChild($doc->createTextNode($this->meta->n));
			$meta->appendChild($name);
		}
		
		// start "locations" block;
		$locs = $doc->createElement('locations');
		
		foreach ($this->venues as $venue) {
			
			$loc = $doc->createElement("loc");
			
			// id
			$name = $doc->createAttribute("key");
			$name->appendChild($doc->createTextNode($venue->id));
			$loc->appendChild($name);
			
			if ($venue->flag && $venue->flag != "0") {
				$name = $doc->createAttribute("flag");
				$name->appendChild($doc->createTextNode($venue->flag));
				$loc->appendChild($name);
			}
			
			// name
			$name = $doc->createElement("name");
			$name->appendChild($doc->createTextNode($venue->name));
			$loc->appendChild($name);
			
			if ($venue->street) {
				$name = $doc->createElement("addr");
				$name->appendChild($doc->createTextNode($venue->street));
				$loc->appendChild($name);
			}
			
			if (!$minimal) {
				if ($venue->city) {
				$name = $doc->createElement("city");
				$name->appendChild($doc->createTextNode($venue->city));
				$loc->appendChild($name);
				}
				
				if ($venue->state) {
				$name = $doc->createElement("state");
				$name->appendChild($doc->createTextNode($venue->state));
				$loc->appendChild($name);
				}
				
				if ($venue->zipcode) {
				$name = $doc->createElement("zipcode");
				$name->appendChild($doc->createTextNode($venue->zipcode));
				$loc->appendChild($name);
				}
				
				if ($venue->neighborhood) {
					$name = $doc->createElement("neighborhood");
					$name->appendChild($doc->createTextNode($venue->neighborhood));
					$loc->appendChild($name);
				}
				
				if ($venue->phone) {
				$name = $doc->createElement("phone");
				$name->appendChild($doc->createTextNode($venue->phone));
				$loc->appendChild($name);
				}
				
			}
			
			if ($venue->lat) {
			$name = $doc->createElement("lat");
			$name->appendChild($doc->createTextNode($venue->lat));
			$loc->appendChild($name);
			}
			
			if ($venue->lon) {
			$name = $doc->createElement("lon");
			$name->appendChild($doc->createTextNode($venue->lon));
			$loc->appendChild($name);
			}
			
			//$name = $doc->createElement("dist");
			//$name->appendChild($doc->createTextNode(sprintf("%01.2f", $d)));
			//$loc->appendChild($name);
			
			if (!$minimal) {

        if ($venue->dist) {
          $name = $doc->createElement("dist");
          $name->appendChild($doc->createTextNode($venue->dist));
          $loc->appendChild($name);
        }

        if ($venue->updated) {
          $name = $doc->createElement("date");
          $name->appendChild($doc->createTextNode(substr($venue->updated, 0, 10)));
          $loc->appendChild($name);
        }

				if ($venue->created) {
					$name = $doc->createElement("created");
					$name->appendChild($doc->createTextNode(substr($venue->created, 0, 10)));
					$loc->appendChild($name);
				}

        if ($venue->source) {
          $name = $doc->createElement("source");
          $name->appendChild($doc->createTextNode($venue->source));
          $loc->appendChild($name);
        }

        if ($venue->url) {
          $name = $doc->createElement("url");
          $name->appendChild($doc->createTextNode($venue->url));
          $loc->appendChild($name);
        }

        if ($venue->fsqid) {
          $name = $doc->createElement("fsqid");
          $name->appendChild($doc->createTextNode($venue->fsqid));
          $loc->appendChild($name);
        }

        if (!empty($venue->games)) {
          foreach ($venue->games as $g) {

            $game = $doc->createElement("game");

            $name = $doc->createAttribute("key");
            $name->appendChild($doc->createTextNode($g->id));
            $game->appendChild($name);

            if ($g->deleted) {
              $name = $doc->createAttribute("deleted");
              $name->appendChild($doc->createTextNode($g->deleted));
              $game->appendChild($name);
            }

            if ($g->new) {
              $name = $doc->createAttribute("new");
              $name->appendChild($doc->createTextNode($g->new));
              $game->appendChild($name);
            }

            if ($g->rare) {
              $name = $doc->createAttribute("rare");
              $name->appendChild($doc->createTextNode($g->rare));
              $game->appendChild($name);
            }

            $name = $doc->createElement("abbr");
            $name->appendChild($doc->createTextNode($g->abbr));
            $game->appendChild($name);

            $name = $doc->createElement("cond");
            $name->appendChild($doc->createTextNode($g->cond));
            $game->appendChild($name);

            $name = $doc->createElement("price");
            $name->appendChild($doc->createTextNode($g->price));
            $game->appendChild($name);

            if ($g->name) {
              $name = $doc->createElement("fullname");
              $name->appendChild($doc->createTextNode($g->name));
              $game->appendChild($name);
            }

            $name = $doc->createElement("ipdb");
            $name->appendChild($doc->createTextNode($g->ipdb));
            $game->appendChild($name);

            if ($g->manufacturer) {
              $name = $doc->createElement("manufacturer");
              $name->appendChild($doc->createTextNode($g->manufacturer));
              $game->appendChild($name);
            }

            if ($g->year) {
              $name = $doc->createElement("year");
              $name->appendChild($doc->createTextNode($g->year));
              $game->appendChild($name);
            }

            $loc->appendChild($game);

          }
        }

        if (!empty($venue->comments)) {
          foreach ($venue->comments as $c) {

            $comment = $doc->createElement("comment");

            $name = $doc->createAttribute("key");
            $name->appendChild($doc->createTextNode($c->id));
            $comment->appendChild($name);

            $name = $doc->createElement("ctext");
            $name->appendChild($doc->createTextNode($c->text));
            $comment->appendChild($name);

            if ($c->date) {
              $name = $doc->createElement("cdate");
              $name->appendChild($doc->createTextNode(str_replace('T', ' ', substr($c->date, 0, 19))));
              $comment->appendChild($name);
            }

            $loc->appendChild($comment);

          }
        }
				
				if (count($venue->images) > 0) {
					
					$images = $doc->createElement("images");
					
					$name = $doc->createAttribute("count");
					$name->appendChild($doc->createTextNode(count($venue->images)));
					$images->appendChild($name);
					
					foreach ($venue->images as $i) {
						
						$image = $doc->createElement("image");
						
						$name = $doc->createAttribute("url");
						$name->appendChild($doc->createTextNode($i->imageurl));
						$image->appendChild($name);
						
						if ($i->default == "1") {
							$name = $doc->createAttribute("default");
							$name->appendChild($doc->createTextNode($i->default));
							$image->appendChild($name);
						}
						
						if ($i->thumburl) {
							$name = $doc->createAttribute("thumb");
							$name->appendChild($doc->createTextNode($i->thumburl));
							$image->appendChild($name);
						}
							
						$images->appendChild($image);
						
					}
					
					$loc->appendChild($images);
					
				}
				
				if (count($venue->leagues) > 0) {
					
					$leagues = $doc->createElement("leagues");
					
					$name = $doc->createAttribute("count");
					$name->appendChild($doc->createTextNode(count($venue->leagues)));
					$leagues->appendChild($name);
					
					foreach ($venue->leagues as $l) {
						
						$league = $doc->createElement("league");
						
						$name = $doc->createAttribute("key");
						$name->appendChild($doc->createTextNode($l->id));
						$league->appendChild($name);
						
						$name = $doc->createElement("leaguename");
						$name->appendChild($doc->createTextNode($l->name));
						$league->appendChild($name);						
						
						if (count($l->teams) > 0) {
							
							$teams = $doc->createElement("teams");
							
							$name = $doc->createAttribute("count");
							$name->appendChild($doc->createTextNode(count($l->teams)));
							$teams->appendChild($name);
							
							foreach ($l->teams as $t) {
								
								$team = $doc->createElement("team");
								
								$name = $doc->createAttribute("key");
								$name->appendChild($doc->createTextNode($t->id));
								$team->appendChild($name);
								
								$name = $doc->createElement("teamname");
								$name->appendChild($doc->createTextNode($t->name));
								$team->appendChild($name);
								
								$teams->appendChild($team);
								
							}
							
							$league->appendChild($teams);
							
						}
						
						$leagues->appendChild($league);
						
					}
					
					$loc->appendChild($leagues);
					
				}

        if (!empty($venue->tournaments)) {
          foreach ($venue->tournaments as $t) {

            $contest = $doc->createElement("contest");

            $name = $doc->createAttribute("key");
            $name->appendChild($doc->createTextNode($t->id));
            $contest->appendChild($name);

            if ($t->ifpaId) {
              $name = $doc->createAttribute("ifpa");
              $name->appendChild($doc->createTextNode($t->ifpaId));
              $contest->appendChild($name);
            }

            $name = $doc->createElement("desc");
            $name->appendChild($doc->createTextNode($t->name));
            $contest->appendChild($name);

            if ($t->dateFrom) {

              $name = $doc->createElement("contestdate");
              $name->appendChild($doc->createTextNode($t->dateFrom));
              $contest->appendChild($name);

            }

            $loc->appendChild($contest);

          }
        }
				
			}
			
			$locs->appendChild($loc);
			
		}
		
		$name = $doc->createAttribute("count");
		$name->appendChild($doc->createTextNode(count($this->venues)));
		$locs->appendChild($name);
		
		// add game descriptions from dict array;
		$dict = $doc->createElement("dict");
		
		foreach ($this->meta->gamedict->en as $key => $desc) {
			
			$entry = $doc->createElement("entry");
			$name = $doc->createAttribute("key");
			$name->appendChild($doc->createTextNode($key));
			$entry->appendChild($name);
			$entry->appendChild($doc->createTextNode($desc));
			$dict->appendChild($entry);
		
		}
		
		$meta->appendChild($dict);
		
		if ($this->meta->stats) {
			
			// stats;
			$stats = $doc->createElement("stats");
			
			// notifications attribute;
			$name = $doc->createAttribute("notifications");
			$name->appendChild($doc->createTextNode($this->meta->stats->notifications));
			$stats->appendChild($name);
			
			$meta->appendChild($stats);
			
		}
		
		if ($this->meta->message) {
			
			$msg = $doc->createElement("message");
			
			$name = $doc->createElement("title");
			$name->appendChild($doc->createTextNode($this->meta->message->title));
			$msg->appendChild($name);
			
			$name = $doc->createElement("body");
			$name->appendChild($doc->createTextNode($this->meta->message->body));
			$msg->appendChild($name);
			
			$meta->appendChild($msg);
			
		}
		
		if (!$minimal) {
			// add meta to root;
			$r->appendChild($meta);
		}
		
		// add locs to root;
		$r->appendChild($locs);
		
		return $doc->saveXML();
		
	}
}

class Image {
	public $id;
	public $imageurl;
	public $thumburl;
	public $default;
}

class Venue {
	public $id;
	public $name;
	public $street;
	public $city;
	public $state;
	public $zipcode;
	public $neighborhood;
	public $country;
	public $lat;
	public $lon;
	public $phone;
	public $source;
  public $sourceid;
	public $url;
	public $dist;
	public $created;
	public $updated;
	public $flag;
	public $fsqid;
	public $games;
	public $comments;
	public $images;
	public $leagues;
	public $tournaments;
	function __construct() {
		$this->games = array();
		$this->comments = array();
		$this->images = array();
		$this->leagues = array();
		$this->tournaments = array();
	}
	public function addGame($game) {
		$this->games[] = $game;
	}
	public function addComment($comment) {
		$this->comments[] = $comment;
	}
	public function addImage($image) {
		$this->images[] = $image;
	}
	public function addLeague($league) {
		$this->leagues[] = $league;
	}
	public function addTournament($tournament) {
		$this->tournaments[] = $tournament;
	}
	function __sleep() {
		$keys = array();
		foreach (get_object_vars($this) as $key => $var) {
			if ($var != null) {
				$keys[] = $key;
			}
		}
		return $keys;
	}
}

class MinimalVenue {
  public $id;
  public $name;
  public $lat;
  public $lon;
}

class Game {
	public $id;
	public $abbr;
	public $cond;
	public $price;
	public $name;
	public $ipdb;
	public $new;
	public $rare;
	public $flag;
	public $deleted;
	public $manufacturer;
	public $year;
}

class Comment {
	public $id;
	public $date;
	public $text;
}

class League {
	public $id;
	public $name;
	public $teams;
	public function __construct() {
		$this->teams = array();
	}
	public function addTeam($team) {
		$this->teams[] = $team;
	}
}

class Team {
	public $id;
	public $name;
}

class User {
	public $id;
	public $username;
	public $password;
	public $lname;
	public $fname;
	public $uuid;
	public $tokens;
	public $notifications;
        public $banned;
	public function __construct() {
		$this->tokens = array();
		$this->notifications = array();
	}
}

class Token {
	public $id;
	public $service;
	public $token;
	public $userid;
}

class Notification {
	public $id;
	public $message;
	public $touserid;
	public $global;
	public $extra;
}

class Tournament {
	public $id;
	public $name;
	public $dateFrom;
	public $dateThru;
	public $venueId;
	public $priorVenueId;
	public $ifpaId;
}

class Message {
	public $id;
	public $title;
	public $body;
}


?>