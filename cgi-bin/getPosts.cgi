#!"C:/Program Files (x86)/Ampps/php/php-cgi.exe" -q

<?php

require('../BackEnd/SecurityManager.php');
//null = post
$expectedGet = 'listItems';
$security = new SecurityManager($expectedGet);
$security->validateSession();
//Post: if true = strict check, false = allow whitespace and -.!@
$security->initializeSecurity(true);

require('../BackEnd/DB.php');

if(isset($_GET[$expectedGet])){

    $query = "SELECT * FROM posts";
    $query = $db->prepare($query);
        try{
            $query->execute();
        } catch (PDOException $e) {
            exit($e);
        } finally {
            if(!empty($check = $query->fetchAll(PDO::FETCH_ASSOC))){
                $encode = json_encode($check);
                print_r($encode);
            }else{
                exit("No data found");
            }
        }
}

?>