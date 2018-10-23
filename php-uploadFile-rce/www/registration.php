<?php
    require_once    'config.php';

    $mess = '';
    $flag = '';

    if ( $_SERVER[ 'REQUEST_METHOD' ] == 'POST' && $_SESSION['id'] == '' )   {

        require_once 'common.php';
        require_once 'authFunction.php';
        require_once 'fileFunction.php';

        $dbh    = connectDB();

        if ( !varidatRegistration( $_POST['loginId'], $_POST['name'], $_POST['addr'], $_POST['password'] )  ) {

            header("Location: / ");
            exit;

        }

        if ( !registration( $dbh, $_POST['loginId'], $_POST['name'], $_POST['addr'], basename($_FILES['icon']['name']), $_POST['password']) ) {

            header("Location: / ");
            exit;

        }

        fileUpload( $_FILES );
        login( $dbh, $_POST['loginId'], $_POST['password'] );
        $flag = (bool)true ;

        header("Location: / ");
        exit;
    } else if ( !$_SESSION['id'] == '' ) {

        header("Location: / ");
        exit;

    }
    require_once    'template_registration.php';


?>
