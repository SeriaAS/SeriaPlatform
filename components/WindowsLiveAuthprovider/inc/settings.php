<?php

// Specify true to log messages to Web server logs.
$DEBUG = false;

// Comma-delimited list of offers to be used.
$OFFERS = "Contacts.View";

// Application key file: store in an area that cannot be
// accessed from the Web.
$xml = new WindowsLiveXmlConfig($this);
$KEYFILE = $xml->getXmlConfig();

// Name of cookie to use to cache the consent token.
$COOKIE = 'delauthtoken';
$COOKIETTL = time() + (10 * 365 * 24 * 60 * 60);

// URL of Delegated Authentication sample index page.
$INDEX = 'index.php';

// Default handler for Delegated Authentication.
$HANDLER = SERIA_HTTP_ROOT.'/seria/components/WindowsLiveAuthprovider/pages/handler.php';

// The CSS style to use for the page body.
$BODYSTYLE = <<<END
    <style type="text/css">
      table
      {
        font-family: verdana;
        font-size: 10pt;
        color: black;
        background-color: white;
      }
      h1
      { 
        font-family: verdana;
        font-size: 10pt;
        font-weight: bold;
        color: #0070C0;
      }
    </style>
END;
