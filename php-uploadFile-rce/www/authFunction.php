<?php

    function varidatRegistration (string $loginId, string $name, string $addr, string $password ):bool  {

        if ($lodinId === '' || $name === '' || $addr === '' || $password === '') {

            return (bool)false ;

        }

        return (bool)true ;

    }

    function varidatLogin (string $loginId, string $password):bool {

        if ( $loginId == '' || $password == '' ) {

            return (bool)false ;

        }

        return (bool)true ;

    }

    function registration ( $dbh, string $loginId, string $name, string $addr, $icon, string $password ):bool {

        if ( $icon === '' ) {

            $icon = 'null.png';

        }

        $query      = 'INSERT INTO users (`id`, `loginId`, `name`, `addr`, `icon`, `password`) VALUES (:id, :loginId, :name, :addr, :icon, :password);';
        $stmt       = $dbh->prepare($query);
        $id         = Null;
        $password   = password_hash($password,PASSWORD_DEFAULT);

        try {

            $stmt->bindParam(":id",         $id,        PDO::PARAM_INT );
            $stmt->bindParam(":loginId",    $loginId,   PDO::PARAM_STR );
            $stmt->bindParam(":name",       $name,      PDO::PARAM_STR );
            $stmt->bindParam(":addr",       $addr,      PDO::PARAM_STR );
            $stmt->bindParam(":icon",       $icon,      PDO::PARAM_STR );
            $stmt->bindParam(":password",   $password,  PDO::PARAM_STR );

            $stmt->execute();

            $ret = (bool)true ;

        } catch ( PDOException $e ) {

            $ret = (bool)false;
        }

        return $ret;

    }

    function login ( $dbh, string $login, string $password):bool {

        $query  = " SELECT * FROM users WHERE loginid = :loginId; " ;

        try {

            $stmt   = $dbh->prepare($query);

            $stmt->bindParam(':loginId', $login, PDO::PARAM_STR);
            $stmt->execute();

            $usersData = $stmt->fetchAll();
            $ret       = (bool)true;

        } catch (PDOException $e) {

            $ret = (bool)false ;
        }
        if ( !password_verify($password, $usersData[0]['password']) ) {

            $ret = (bool)false ;

        }
        if ($ret) {

            session_regenerate_id(TRUE);
            $_SESSION["userName"]   = $usersData[0]['loginId'];
            $_SESSION["id"]         = $usersData[0]['id'];

        }

        return $ret ;
    }
