#!"C:/Program Files (x86)/Ampps/php/php-cgi.exe" -q

<?php
//accept ajax requests only
if(!isset($_SERVER['HTTP_X_REQUESTED_WITH']) AND strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) != 'xmlhttprequest') {
        die();
}else{
    require('../BackEnd/DB.php');
    $data = file_get_contents("php://input");
}
//check input
if(isset($data["username"], $data["password"], $data["email"])){

    $data = json_decode($data, true);
    $username = $data["username"];
    $password = $data["password"];
    $email = $data["email"];
    //escape SQL injection with prebuilt q
    $query = "INSERT INTO users(username, password, email) VALUES(:username, :password, :email)";
    $query = $db->prepare($query);
    if(!$results = $query->execute(array(
        ":username" => $username,
        ":password" => $password,
        ":email" => $email
    ))){
        print_r($query->errorInfo());
    }
    print_r(1);

}

?>