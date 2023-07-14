<?php

echo '<h1>Google auth test callback</h1>';

echo '<h4>GET:</h4>';
echo '<pre>';
var_dump($_GET);
echo '</pre>';
echo '<br>';

echo '<h4>SESSION:</h4>';
echo '<pre>';
var_dump($_SESSION);
echo '</pre>';
echo '<br>';

if($_GET['code']) {
    echo '<h2>Authorization code received</h2>';
    echo '<p>'.$_GET['code'].'</p>';
    echo '<br>';
    echo '<h3>Following scopes available:</h3>';

    $scopes = explode(' ', $_GET['scope']);

    if(count($scopes) > 0) {
        echo '<ul>';
        foreach ($scopes as $scope) {
            echo '<li>' . $scope . '</li>';
        }
        echo '</ul>';
    }

} else {
    echo '<h2>ERROR: Authorization code NOT received</h2>';
}

echo '<br>';
echo '<h3>finished</h3>';

exit;

?>

