<?php

function rick_roll() {
	Redirect("http://www.youtube.com/watch?v=dQw4w9WgXcQ");
}

// "safe redirect" courtesy php docs; "Yousha dot A at Mail dot com";
function Redirect($Str_Location, $Bln_Replace = 1, $Int_HRC = NULL)
{
        if(!headers_sent())
        {
            header('location: ' . urldecode($Str_Location), $Bln_Replace, $Int_HRC);
            exit;
        }

    exit('<meta http-equiv="refresh" content="0; url=' . urldecode($Str_Location) . '"/>'); # | exit('<script>document.location.href=' . urldecode($Str_Location) . ';</script>');
    return;
}

?>