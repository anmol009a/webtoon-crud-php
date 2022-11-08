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
	 * @todo update webtoon url only when o new chapter is inserted
	 * @todo update cover url
	 */
	function insert_webtoons_data(array $webtoon_data)
	{
		$sql = "INSERT IGNORE INTO webtoons (title, url)  VALUES (?, ?)";	// sql stmt		
		$stmt = $this->connection->prepare($sql);	// prepare stmt
		$stmt->bind_param("ss", $this->title, $this->url);	// bind parameters

		//for each webtoon data
		foreach ($webtoon_data as $webtoon) {
			$this->title = $webtoon->title;
			$this->url = $webtoon->url;
			$stmt->execute();	// execute sql
		}

		// get webtoons id
		$webtoon_data = $this->get_webtoons_id($webtoon_data);

		// insert chapters
		$this->insert_chapters($webtoon_data);

		// insert covers
		$this->insert_covers($webtoon_data);
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

	/**
	 * @param array $webtoon_data of objects with w_id
	 */
	function insert_chapters(array $webtoon_data)
	{
		// define sql stmt
		$sql = "INSERT IGNORE INTO chapters (w_id, number, url)  VALUES (?, ?, ?)";
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
	 * @param array $webtoon_data of objects with w_id
	 */
	function insert_covers(array $webtoon_data)
	{
		// define sql stmt
		$sql = "INSERT IGNORE INTO covers (w_id, url)  VALUES (?, ?) ON DUPLICATE KEY UPDATE";
		$stmt = $this->connection->prepare($sql);
		$stmt->bind_param("is", $this->w_id, $this->cover_url); // bind parameters

		// for each webtoon insert cover
		foreach ($webtoon_data as $webtoon) {
			if ($webtoon->id and $webtoon->cover_url) {
				$this->w_id = $webtoon->id;   // webtoon id
				$this->cover_url = $webtoon->cover_url;   // cover url
				$stmt->execute();   // execute query
			}
		}
	}


	/**
	 * @param array $webtoon_data of objects with w_id
	 */
	function update_webtoon_url(int $w_id, string $url)
	{
		// define sql stmt
		$sql = "UPDATE webtoons SET (url) VALUES ? where w_id = ?";
		$stmt = $this->connection->prepare($sql);
		$stmt->bind_param("si", $w_id, $url); // bind parameters

		if ($w_id and $url) {
			$stmt->execute();   // execute query
		}
	}

	/**
	 * @param int $limit no of webtoons
	 * @return array of objects
	 */
	function get_webtoons(int $limit = 30, int $offset = 0)
	{
		// define sql stmt
		$sql = "SELECT webtoons.id, title, webtoons.url, covers.url as cover_url FROM `webtoons` LEFT JOIN covers ON webtoons.id = covers.w_id ORDER BY webtoons.updated_at Desc LIMIT $limit OFFSET $offset;";

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
	 * Returns given webtoon data with webtoon id
	 * @return array of objects
	 */
	function get_webtoons_id(array $webtoon_data)
	{
		// define sql stmt
		$sql = "SELECT id FROM `webtoons` WHERE title = ?;";
		$stmt = $this->connection->prepare($sql);
		$stmt->bind_param("s", $this->title); // bind parameters

		foreach ($webtoon_data as $webtoon) {
			$this->title = $webtoon->title;
			// execute sql
			$stmt->execute();
			$result = $stmt->get_result();
			if ($result) {
				$webtoon->id = mysqli_fetch_column($result, 0);
			}
		}

		// returns an array of objects
		return $webtoon_data;
	}

	/**
	 * @param int $w_id webtoon id
	 * @param int $limit no of chapters
	 * @return array of objects
	 */
	function get_chapters(int $w_id, int $limit = 2)
	{

		// define sql stmt
		$sql = "SELECT * FROM chapters WHERE w_id = $w_id ORDER BY number DESC LIMIT $limit";

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
		$sql = "SELECT webtoons.id, title, webtoons.url,covers.url as cover_url FROM `webtoons` 
		LEFT JOIN 
		covers ON webtoons.id = covers.w_id WHERE title LIKE '%$query%' ORDER BY webtoons.updated_at Desc LIMIT $limit;";

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
}
