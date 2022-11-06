<?php

namespace WebtoonCrud;

class WebtoonCrud
{
	protected $w_id;
	protected $title;
	protected $url;
	protected $cover_url;
	protected $number;
	protected $chapter_url;

	/**
	 * constructor
	 * @param $conn takes mysql db connection object
	 */
	public function __construct(object $conn)
	{
		// object which represents the connection to a MySQL Server
		$this->connection = $conn;
	}

	/**
	 * @todo insert cover with webtoon
	 */
	function insert_webtoons(array $webtoon_data)
	{
		$sql = "INSERT IGNORE INTO webtoons (title, url)  VALUES (?, ?)";	// sql stmt		
		$stmt = $this->connection->prepare($sql);	// prepare stmt
		// bind parameters
		$stmt->bind_param("ss", $this->title, $this->url);	// bind parameters

		//for each webtoon data
		foreach ($webtoon_data as $webtoon) {
			$this->title = $webtoon->title;
			$this->url = $webtoon->url;
			$stmt->execute();	// execute sql

			// get webtoon id and store it in $webtoon_data::$webtoon
			$webtoon->id = $this->connection->insert_id;
		}

		$this->insert_covers($webtoon_data);	// insert cover url
	}

	function insert_chapters(array $webtoon_data)
	{
		// define sql stmt
		$sql = "INSERT INTO chapters (w_id, number, url)  VALUES (?, ?, ?)";
		$stmt = $this->connection->prepare($sql);
		$stmt->bind_param("ids", $this->w_id, $this->number, $this->url); // bind parameters

		// for each webtoon
		foreach ($webtoon_data as $webtoon) {

			$this->w_id = $webtoon->id;   // webtoon id

			// for every chapter
			foreach ($webtoon->chapters as $chapter) {
				$this->number = $chapter->number; // chapter number
				$this->url = $chapter->url;   // chapter url;
				$stmt->execute();   // execute query
			}
		}
	}

	/**
	 * @param array $webtoon_data of objects with w_id not null
	 */
	function insert_covers(array $webtoon_data)
	{
		// define sql stmt
		$sql = "INSERT INTO covers (w_id, url)  VALUES (?, ?)";
		$stmt = $this->connection->prepare($sql);
		$stmt->bind_param("is", $this->w_id, $this->cover_url); // bind parameters

		// for each webtoon insert cover
		foreach ($webtoon_data as $webtoon) {
			if ($webtoon->id) {
				$this->w_id = $webtoon->id;   // webtoon id
				$this->cover_url = $webtoon->cover_url;   // cover url
				$stmt->execute();   // execute query
			}
		}
	}

	/**
	 * @param array $webtoon_data of objects with w_id not null
	 */
	function update_covers(array $webtoon_data)
	{
		// define sql stmt
		$sql = "UPDATE covers SET (url) VALUES ? where w_id = ?";
		$stmt = $this->connection->prepare($sql);
		$stmt->bind_param("si", $this->w_id, $this->cover_url); // bind parameters

		// for each webtoon update cover
		foreach ($webtoon_data as $webtoon) {
			if ($webtoon->id) {
				$this->w_id = $webtoon->id;   // webtoon id
				$this->cover_url = $webtoon->cover_url;   // cover url
				$stmt->execute();   // execute query
			}
		}
	}

	/**
	 * @param int $limit no of webtoons
	 * @return array of objects
	 */
	function get_webtoons(int $limit = 30)
	{
		// define sql stmt
		$sql = "SELECT * FROM webtoons ORDER BY updated_at DESC LIMIT $limit";

		// execute query
		$result = mysqli_query($this->connection, $sql);

		// fetch result
		$rows = mysqli_fetch_all($result, MYSQLI_ASSOC);

		foreach ($rows as  $key => $row) {
			$rows[$key]['chapters'] = $this->get_chapters($row['id']);
		}

		// returns an array of objects
		return json_decode(json_encode($rows));
	}

	/**
	 * @param int $w_id webtoon id
	 * @param int $limit no of chapters
	 * @return array of objects
	 */
	function get_chapters(int $w_id, int $limit = 2)
	{

		// define sql stmt
		$sql = "SELECT * FROM chapters WHERE w_id = $w_id ORDER BY 'number' DESC LIMIT $limit";

		// execute query
		$result = mysqli_query($this->connection, $sql);

		// fetch result
		$rows = mysqli_fetch_all($result, MYSQLI_ASSOC);

		// returns an array of objects
		return json_decode(json_encode($rows));
	}

	/**
	 * @param int $limit no of webtoons
	 * @return array of objects
	 */
	function search_webtoon(string $query, int $limit = 10)
	{
		// define sql stmt
		$sql = "SELECT * FROM webtoons WHERE title LIKE '%$query%' ORDER BY updated_at DESC LIMIT $limit";

		// execute query
		$result = mysqli_query($this->connection, $sql);

		// fetch result
		$rows = mysqli_fetch_all($result, MYSQLI_ASSOC);

		// returns an array of objects
		return json_decode(json_encode($rows));
	}
}
