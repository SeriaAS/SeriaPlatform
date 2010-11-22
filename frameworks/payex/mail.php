<?php

function sendMail($to, $name, $subject, $message)
        {
                require_once(dirname(__FILE__)."/PHPMailer_v5.0.0/class.phpmailer.php");

                // $message skal være contents i nyhetsbrev malen.
                /* IMPLEMENTER HVIS GLIMTSHOP SKAL HA EN MAL
		$replace = "%CONTENTS%";
                $textOnly=$message;
                $message = str_replace($replace, $message, file_get_contents("./url/til/en/mal.php"));
		*/
                $mail = new PHPMailer();

                // config

                $mail->CharSet = "utf-8";
                $mail->Hostname = "citynetwork1.seria.no";

                // sender
                $mail->From = "glimtshop@glimt.no";
                $mail->FromName = "Glimtshop";

                // reciever

                        $mail->AddAddress($to, $name);

                $mail->AddBCC("frode@seria.no");
                $mail->AddBCC("oyvind@seria.no");
                $mail->AddBCC("joakim@seria.no");
                $mail->AddReplyTo("glimtshop@glimt.no", "Glimtshop");

                // message

                $mail->Subject = $subject;
                $mail->Body = $message;

                $textOnly=strip_tags($textOnly);
                $mail->AltBody = $textOnly;
                if(!($mail->send()))
                        throw new Exception("Kunne ikke sende e-post.");

                return true;
        }
?>
