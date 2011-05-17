<?php
require_once 'database.php';
require_once 'config/database.php';

$db = new Database($config);
$videos = $db->get_videos->by_date_added('0000-00-00');

foreach($videos as $video){
    echo $video->title.'<br/>';
}
echo '<hr/>';
$video = $db->get_videos->first_by_date_added_AND_id('0000-00-00', '0hXrlOiApDQ', 'id DESC');

echo $video->title;
?>