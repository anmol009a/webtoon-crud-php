<?php

namespace WebtoonCrud;

class WebtoonCrud
{
	protected int $w_id;
	protected string $title;
	protected string $url;
	protected string $cover_url;
	protected int $number;
	protected string $chapter_url;

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
		// insert webtoons
		foreach ($webtoon_data as $webtoon) {
			$webtoon->id =	$this->insert_webtoon($webtoon->title, $webtoon->url);

			// if new webtoon inserted
			if ($webtoon->id !== -1) {
				// insert chapters
				foreach ($webtoon->chapters as $chapter) {
					$this->insert_chapter($webtoon->id, $chapter->number, $chapter->url);
				}

				// insert cover
				$this->insert_cover($webtoon->id, $webtoon->cover_url);
			} else {
				// get webtoons id
				$webtoon->id = $this->get_webtoon_id($webtoon->title);

				// insert chapters
				foreach ($webtoon->chapters as $chapter) {
					$webtoon->update_url =	$this->insert_chapter($webtoon->id, $chapter->number, $chapter->url);
				}

				// update webtoon url if new chapter inserted
				if ($webtoon->update_url) {
					$this->update_webtoon_url($webtoon->id, $webtoon->url);
				}

				// update cover
				$this->update_cover($webtoon->id, $webtoon->cover_urd);
			}
		}
	}

	/**
	 * Insert webtoon
	 * @return int webtoon id on insert otherwise -1
	 */
	function insert_webtoon(string $title, string $url)
	{
		$sql = "INSERT INTO webtoons (title, url)  VALUES (?, ?)";	// sql stmt		
		$stmt = $this->connection->prepare($sql);	// prepare stmt
		// bind parameters
		$stmt->bind_param("ss", $title, $url);	// bind parameters

		try {
			// execute sql
			$stmt->execute();

			// return webtoon id
			return $this->connection->insert_id;
		} catch (\mysqli_sql_exception $exception) {
			echo $exception->getMessage() . "\n";
		}
		return -1;
	}


	/**
	 * Update webtoon url
	 * @param int $id webtoon id
	 * @param string $url webtoon url
	 */
	function update_webtoon_url(int $id, string $url)
	{
		$sql_update = "UPDATE webtoons SET url = ? WHERE id = ?;";
		$stmt_update = $this->connection->prepare($sql_update);
		$stmt_update->bind_param("si", $url, $id); // bind parameters

		// update webtoon url
		$stmt_update->execute();   // execute query
	}

	/**
	 * Insert Chapter
	 * @return bool if chapter inserted or not
	 */
	function insert_chapter(int $w_id, int $number, string $url)
	{
		// define sql stmt
		$sql = "INSERT INTO chapters (w_id, number, url)  VALUES (?, ?, ?)";
		$stmt = $this->connection->prepare($sql);
		$stmt->bind_param("ids", $w_id, $number, $url); // bind parameters

		try {
			$stmt->execute();   // execute query
			return true;
		} catch (\mysqli_sql_exception $th) {
			$this->connection->rollback();
			echo $th->getMessage() . "\n";
		}
		return false;
	}


	/**
	 * @param int $w_id
	 * @param string $url
	 */
	function insert_cover(int $w_id, string $url)
	{
		// define sql stmt
		$sql = "INSERT INTO covers (w_id, url)  VALUES (?, ?) ON DUPLICATE KEY UPDATE url = ?";
		$stmt = $this->connection->prepare($sql);
		$stmt->bind_param("iss", $w_id, $url, $url); // bind parameters

		if ($w_id and $url) {
			try {
				$stmt->execute();   // execute query
			} catch (\mysqli_sql_exception $th) {
				echo $th->getMessage() . "\n";
			}
		}
	}

	/**
	 * @param int $w_id
	 * @param string $url
	 */
	function update_cover(int $w_id, string $url)
	{
		// define sql stmt
		$sql = "UPDATE covers SET url = ? WHERE w_id = ?";
		$stmt = $this->connection->prepare($sql);
		$stmt->bind_param("si", $url, $w_id); // bind parameters

		if ($w_id and $url) {
			try {
				$stmt->execute();   // execute query
			} catch (\mysqli_sql_exception $th) {
				echo $th->getMessage() . "\n";
			}
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
	 * Get webtoon id
	 * @return int webtoon id
	 */
	function get_webtoon_id(string $title)
	{
		// define sql stmt
		$sql = "SELECT id FROM `webtoons` WHERE title = ?;";
		$stmt = $this->connection->prepare($sql);
		$stmt->bind_param("s", $title); // bind parameters


		$stmt->execute();
		$result = $stmt->get_result();
		if (mysqli_num_rows($result)) {
			return mysqli_fetch_column($result, 0);
		}
		return -1;
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
