<?php

require_once('db_connect.php');

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

	function insert_webtoons(array $webtoon_data)
	{
		// sql stmt
		$sql = "INSERT IGNORE INTO webtoons (title, url)  VALUES (?, ?)";
		// prepare stmt
		$stmt = $this->connection->prepare($sql);
		// bind parameters
		$stmt->bind_param("ss", $this->title, $this->url);

		//for each webtoon data execute sql 
		foreach ($webtoon_data as $webtoon) {
			$this->title = $webtoon->title;
			$this->url = $webtoon->url;
			$stmt->execute();
		}
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

	function insert_covers(array $webtoon_data)
	{
		// define sql stmt
		$sql = "INSERT INTO covers (w_id, url)  VALUES (?, ?)";
		$stmt = $this->connection->prepare($sql);
		$stmt->bind_param("is", $this->w_id, $this->cover_url); // bind parameters

		// for each webtoon insert cover
		foreach ($webtoon_data as $webtoon) {
			$this->w_id = $webtoon->id;   // webtoon id
			$this->cover_url = $webtoon->cover_url;   // cover url
			$stmt->execute();   // execute query
		}
	}

	/**
	 * @return array of associated arrays
	 */
	function get_webtoons()
	{
		// define sql stmt
		$sql = "SELECT * FROM webtoons ORDER BY updated_at DESC LIMIT 30";

		// execute query
		$result = mysqli_query($this->connection, $sql);

		// fetch result
		$rows = mysqli_fetch_all($result, MYSQLI_ASSOC);

		foreach ($rows as  $key => $row) {
			$rows[$key]['chapters'] = $this->get_chapters($row['id']);
		}

		// return an array of associated arrays
		return $rows;
	}

	/**
	 * @return array of associated arrays
	 */
	function get_chapters(int $w_id)
	{

		// define sql stmt
		$sql = "SELECT * FROM chapters WHERE w_id = $w_id ORDER BY 'number' DESC LIMIT 2";

		// execute query
		$result = mysqli_query($this->connection, $sql);

		// fetch result
		$rows = mysqli_fetch_all($result, MYSQLI_ASSOC);

		// return an array of associated arrays
		return $rows;
	}

	/**
	 * @return array of associated arrays
	 */
	function search_webtoon(string $query)
	{
		// define sql stmt
		$sql = "SELECT * FROM webtoons WHERE title LIKE '%$query%' ORDER BY updated_at DESC LIMIT 30";

		// execute query
		$result = mysqli_query($this->connection, $sql);

		// fetch result
		$rows = mysqli_fetch_all($result, MYSQLI_ASSOC);

		// return an array of associated arrays
		return $rows;
	}
}


// $webtoon_data = [
// 	(object) [
// 		'id' => '85',
// 		'chapters' => [
// 			(object)[
// 				'number' => 23,
// 				'url' => '23.html'
// 			]
// 		],
// 		'title' => 'test11',
// 		'url' => 'test11.html',
// 		'cover_url' => 'test11.png'
// 	],
// 	(object) [
// 		'id' => '86',
// 		'chapters' => [
// 			(object)[
// 				'number' => 20,
// 				'url' => '20.html'
// 			]
// 		],
// 		'title' => 'test12',
// 		'url' => 'test12.html',
// 		'cover_url' => 'test12.png'
// 	]
// ];

// $query = "a";

// $a = new WebtoonCrud($conn);
// $a->insert_webtoons($webtoon_data);
// $a->insert_chapters($webtoon_data);
// $a->insert_covers($webtoon_data);
// echo json_encode($a->get_webtoons($webtoon_data));
// echo json_encode($a->get_chapters(1));
// echo json_encode($a->search_webtoon($query));
