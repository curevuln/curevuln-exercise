<?php
function fileUpdata ($dbh):bool {

    if ( !fileUpload () ) {

        return (bool)false;

    }

    try {

        $query  = 'UPDATE users SET icon = :icon WHERE id = :userId';
        $stmt   = $dbh->prepare($query);

        $stmt->bindParam(':icon',   basename($_FILES['icon']['name']),      PDO::PARAM_STR);
        $stmt->bindParam(':userId', $_SESSION['id'],                        PDO::PARAM_INT);
        $stmt->execute();

    } catch (PDOExecption $e) {

        fileRemove();
        return (bool)false;

    }

    return (bool)true;

}

function fileUpload ():bool {
    $uploaddr  = '/var/www/html/img/';
    $uploadFile = $uploaddr . basename($_FILES['icon']['name']);

    if ( move_uploaded_file($_FILES['icon']['tmp_name'], $uploadFile) ) {

        return (bool)true;

    } else {

        return (bool)false;

    }

}

function fileRemove ():bool {

    $addr  = '/var/www/img/';
    $removeFile = $addr . basename($_FILES['userfile']['name']);
    unlink($removeFile);

    return (bool)true;

}
