<?php
function varidat (string $loginId, string $password):bool {
    if ( $loginId === '' || $password === '' ) {
        return (bool)false ;
    }
    return (bool)true ;
}

function login ( $dbh, string $login, string $password):bool {
    $query  = " SELECT * FROM users WHERE loginid = :loginId; " ;
    try {
        $stmt   = $dbh->prepare($query);
        $stmt->bindParam(':loginId', $login, PDO::PARAM_STR);
        $stmt->execute();
        $usersData = $stmt->fetchAll();
    } catch (PDOException $e) {
        return (bool)false ;
    }
    if ( !password_verify($password, $usersData[0]['password']) ) {
        return (bool)false;
    }
    session_regenerate_id(TRUE);
    $_SESSION["userName"]   = $usersData[0]['loginId'];
    $_SESSION["id"]         = $usersData[0]['id'];
    return (bool)true;
}
