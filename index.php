<?php

class WebtoonCrud
{

    function __constructor()
    {
    }

    function insert_webtoons(array $webtoon_data)
    {
        include_once "db_connect.php";

        $sql = "INSERT IGNORE INTO webtoons (title, url)  VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ss", $title, $url);

        foreach ($webtoon_data as $webtoon) {
            $title = $webtoon->title;
            $url = $webtoon->url;
            $stmt->execute();
            // try {
            // $stmt->execute();
            //     echo "Title: " . $title;
            //     echo "<br>";
            // } catch (Exception $e) {
            //     echo "Failed to insert webtoon details : " . $e->getMessage();
            //     echo "<br>";
            // }
        }
    }

    function insert_chapters(array $webtoon_data)
    {
        // create connection
        include_once "db_connect.php";

        // define sql stmt
        $sql = "INSERT INTO chapters (w_id, number, url)  VALUES (?, ?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ids", $w_id, $number, $url); // bind parameters

        // for each webtoon
        foreach ($webtoon_data as $webtoon) {

            $w_id = $webtoon->id;   // webtoon id

            // for every chapter
            foreach ($webtoon->chapters as $chapter) {
                $number = $chapter->number; // chapter number
                $url = $chapter->url;   // chapter url;
                $stmt->execute();   // execute query
            }
        }
    }

    function insert_covers(array $webtoon_data)
    {
        // create connection
        include_once "db_connect.php";

        // define sql stmt
        $sql = "INSERT INTO covers (w_id, url)  VALUES (?, ?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("is", $w_id, $cover_url); // bind parameters

        // for each webtoon insert cover
        foreach ($webtoon_data as $webtoon) {
            $w_id = $webtoon->id;   // webtoon id
            $cover_url = $webtoon->cover_url;   // cover url
            $stmt->execute();   // execute query
        }
    }

    /**
     * @return array of associated arrays
     */
    function get_webtoons()
    {
        // create connection
        include_once "db_connect.php";

        // define sql stmt
        $sql = "SELECT * FROM webtoons ORDER BY updated_at DESC LIMIT 30";

        // execute query
        $result = mysqli_query($conn, $sql);

        // fetch result
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);

        foreach($rows as $row){
            $chapters = $this->get_chapters($row['id']);
            array_push($row, $chapters);
        }

        print_r($rows);

        // return an array of associated arrays
        return $rows;
    }

    /**
     * @return array of associated arrays
     */
    function get_chapters(int $w_id)
    {
        // create connection
        include_once "db_connect.php";

        // define sql stmt
        $sql = "SELECT * FROM chapters WHERE w_id = $w_id ORDER BY 'number' DESC LIMIT 2";

        // execute query
        $result = mysqli_query($conn, $sql);

        // fetch result
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);

        // print_r($rows);

        // return an array of associated arrays
        return $rows;
    }

    /**
     * @return array of associated arrays
     */
    function search_webtoon(string $query)
    {
        // create connection
        include_once "db_connect.php";

        // define sql stmt
        $sql = "SELECT * FROM webtoons WHERE title LIKE '%$query%' ORDER BY updated_at DESC LIMIT 30";

        // execute query
        $result = mysqli_query($conn, $sql);

        // fetch result
        $rows = mysqli_fetch_all($result, MYSQLI_ASSOC);

        // return an array of associated arrays
        return $rows;
    }
}


$webtoon_data = [
    (object) [
        'id' => '85',
        'chapters' => [
            (object)[
                'number' => 23,
                'url' => '23.html'
            ]
        ],
        'title' => 'test11',
        'url' => 'test11.html',
        'cover_url' => 'test11.png'
    ],
    (object) [
        'id' => '86',
        'chapters' => [
            (object)[
                'number' => 20,
                'url' => '20.html'
            ]
        ],
        'title' => 'test12',
        'url' => 'test12.html',
        'cover_url' => 'test12.png'
    ]
];

$query = "a";

$a = new WebtoonCrud;
// $a->insert_webtoons($webtoon_data);
// $a->insert_chapters($webtoon_data);
// $a->insert_covers($webtoon_data);
// $a->get_webtoons($webtoon_data);
// $a->search_webtoon($query);
// $a->get_chapters(1);
