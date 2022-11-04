<?php
function insert_webtoons(array $webtoon_data)
{
    global $conn;
    $webtoons = [];

    foreach ($webtoon_data as $webtoon) {
        array_push($webtoons, "('$webtoon->title', '$webtoon->url')");
    }

    $webtoons = implode(", ", $webtoons);

    $no_of_values = str_repeat("(? , ?), ", count($webtoons));
    $sql = "INSERT IGNORE INTO webtoons (title, url)  VALUES $nvo";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param("s", $webtoons);
    $stmt->execute() or die($stmt->error);
}