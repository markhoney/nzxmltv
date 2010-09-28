<?php

/*
    This file is part of NZ XMLTV Listings.

    NZ XMLTV Listings is free software: you can redistribute it and/or modify
    it under the terms of the GNU General Public License as published by
    the Free Software Foundation, either version 3 of the License, or
    (at your option) any later version.

    NZ XMLTV Listings is distributed in the hope that it will be useful,
    but WITHOUT ANY WARRANTY; without even the implied warranty of
    MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
    GNU General Public License for more details.

    You should have received a copy of the GNU General Public License
    along with NZ XMLTV Listings.  If not, see <http://www.gnu.org/licenses/>.
*/

/*Need to look for last sentence of either "Starring: Blah", "Voices of: Blah" or "1972" and grab that info to add to the XML. The text should also be left in the description.

    <credits>
      <actor>Blah</actor>
    </credits>
    <date>1972</date>
*/


/*ini_set( 
  'include_path', 
  ini_get( 'include_path' ) . PATH_SEPARATOR . "/home/markhoney/pear/php"
);

include ("Date.php");
$d = new Date(date("Y-m-d H:i:s"));
$d->setTZByID("PST");
$d->convertTZByID("NZ");
$currentdate = $d->format("%Y-%m-%d");

*/

//Comment out for debugging, uncomment for production.
//error_reporting(E_ERROR);

// Variables

function DeclareConstants()
{
 define("telstraDataURL", 'http://www.telstraclear.co.nz/residential/inhome/digital-tv/');//Location of data CFM files
 define("telstraImagesURL", 'http://www.telstraclear.co.nz/images/residential/digital-tv/tv-guide/'); //Location of channel images
 define("yahooTimeFudgeFactor", -2 * 3600);
 define("fulldatadays", 5);
 define("maxDays", 7); //Maximum days a user can get data for
 putenv("TZ=Pacific/Auckland");
 define("currentDate", date('Y-m-d', strtotime(date('Y-m-d'))));
 define("currentDateSQL", date('Y-m-d H:i:s', strtotime(date('Y-m-d'))));
 define("lastfulldatadate", date('Y-m-d', strtotime(currentDate . " +" . fulldatadays . " days")));
 $numberofdays = maxDays; if (isset($_GET["days"])) { if ($_GET["days"] > -1 && $_GET["days"] <= maxDays) { $numberofdays = $_GET["days"]; } }; //If not specified in URL, get maxDays extra days of data. If more than maxDays days are requested, limit to 4 days (the maximum offered by Telstra's TV Guide)
 define("numberofdays", $numberofdays);
 if (isset($_GET["channels"]))
 {
  define("requiredchannels", $_GET["channels"]);
 }
 else
 {
  define("requiredchannels", 'TV1A,TV2A,WSK3,TV4,PEPG');
 }
 define("enddate", date('Y-m-d', strtotime(currentDate . " +" . numberofdays . " days"))); //Set the end date to the start date plus the number of days requested
 $xmlfilenameenddate = ''; if (currentDate <> enddate) { $xmlfilenameenddate = '_' . enddate; } //If more than one day is requested, add the end date to the XML filename
 global $availableSources;
 $availableSources[] = 'TC';
 $availableSources[] = 'YH';
 $availableSources[] = 'FV';
 $availableSources[] = 'All';
 /*$providersresult = mysql_query("SELECT id FROM Sources") or die(mysql_error());
 while ($providername = mysql_fetch_assoc($providersresult))
 {
  $availableSources[] = $providername;
 }*/
 //Folders to be used
 //define("rootFolder", $_SERVER['DOCUMENT_ROOT'] . '/');
 //define("rootFolder", str_ireplace($_SERVER['SCRIPT_NAME'], '', $_SERVER['SCRIPT_FILENAME']) . '/');
 define("libsFolder", dirname(__FILE__) . '/');
 include libsFolder . '../config.php';
 define("xmltvFolder", rootFolder . 'tmp/xmltv/');
 define("xmltvURL", rootURL . 'tmp/xmltv/');
 define("iconsFolder", xmltvFolder . 'icons/channels/');
 define("dtdsFolder", xmltvFolder . 'dtds/');
 define("ratingsFolder", xmltvFolder . 'icons/ratings/');
}

DeclareConstants();

//Generic Functions

function MySQLToXMLDateTime($MySQLDate)
{
 return date('YmdHis', strtotime($MySQLDate));
}

function CleanString($sourceString)
{
 return mysql_real_escape_string(trim($sourceString));
}

function CleanArray($sourceArray)
{
 foreach ($sourceArray as &$element)
 {
  $element = CleanString($element);
 }
 return $sourceArray;
}

function NullQuoteString($string)
{
 return ((is_null($string) OR $string=='') ? 'NULL' : "'$string'");
}

function NullQuoteArray($sourceArray)
{
 foreach ($sourceArray as &$element)
 {
  $element = NullQuoteString($element);
 }
 return $sourceArray;
}

function CleanQuoteString($sourceString)
{
 return NullQuoteString(CleanString($sourceString));
}

function CleanQuoteArray($sourceArray)
{
 return NullQuoteArray(CleanArray($sourceArray));
}

function xmlEntities($str) // Based on http://www.sourcerally.net/Scripts/39-Convert-HTML-Entities-to-XML-Entities, with additions from http://us2.php.net/manual/en/function.get-html-translation-table.php
{
 $html = array('&AElig;','&Aacute;','&Acirc;','&Agrave;','&Alpha;','&Aring;','&Atilde;','&Auml;','&Beta;','&Ccedil;','&Chi;','&Dagger;','&Delta;','&ETH;','&Eacute;','&Ecirc;','&Egrave;','&Epsilon;','&Eta;','&Euml;','&Gamma;','&Iacute;','&Icirc;','&Igrave;','&Iota;','&Iuml;','&Kappa;','&Lambda;','&Mu;','&Ntilde;','&Nu;','&OElig;','&Oacute;','&Ocirc;','&Ograve;','&Omega;','&Omicron;','&Oslash;','&Otilde;','&Ouml;','&Phi;','&Pi;','&Prime;','&Psi;','&Rho;','&Scaron;','&Sigma;','&THORN;','&Tau;','&Theta;','&Uacute;','&Ucirc;','&Ugrave;','&Upsilon;','&Uuml;','&Xi;','&Yacute;','&Yuml;','&Zeta;','&aacute;','&acirc;','&acute;','&aelig;','&agrave;','&alefsym;','&alpha;','&amp;','&and;','&ang;','&apos;','&aring;','&asymp;','&atilde;','&auml;','&bdquo;','&beta;','&brvbar;','&bull;','&cap;','&ccedil;','&cedil;','&cent;','&chi;','&circ;','&clubs;','&cong;','&copy;','&crarr;','&cup;','&curren;','&dArr;','&dagger;','&darr;','&deg;','&delta;','&diams;','&divide;','&eacute;','&ecirc;','&egrave;','&empty;','&emsp;','&ensp;','&epsilon;','&equiv;','&eta;','&eth;','&euml;','&euro;','&exist;','&fnof;','&forall;','&frac12;','&frac14;','&frac34;','&frasl;','&gamma;','&ge;','&gt;','&hArr;','&harr;','&hearts;','&hellip;','&iacute;','&icirc;','&iexcl;','&igrave;','&image;','&infin;','&int;','&iota;','&iquest;','&isin;','&iuml;','&kappa;','&lArr;','&lambda;','&lang;','&laquo;','&larr;','&lceil;','&ldquo;','&le;','&lfloor;','&lowast;','&loz;','&lrm;','&lsaquo;','&lsquo;','&lt;','&macr;','&mdash;','&micro;','&middot;','&minus;','&mu;','&nabla;','&nbsp;','&ndash;','&ne;','&ni;','&not;','&notin;','&nsub;','&ntilde;','&nu;','&oacute;','&ocirc;','&oelig;','&ograve;','&oline;','&omega;','&omicron;','&oplus;','&or;','&ordf;','&ordm;','&oslash;','&otilde;','&otimes;','&ouml;','&para;','&part;','&permil;','&perp;','&phi;','&pi;','&piv;','&plusmn;','&pound;','&prime;','&prod;','&prop;','&psi;','&quot;','&rArr;','&radic;','&rang;','&raquo;','&rarr;','&rceil;','&rdquo;','&real;','&reg;','&rfloor;','&rho;','&rlm;','&rsaquo;','&rsquo;','&sbquo;','&scaron;','&sdot;','&sect;','&shy;','&sigma;','&sigmaf;','&sim;','&spades;','&sub;','&sube;','&sum;','&sup1;','&sup2;','&sup3;','&sup;','&supe;','&szlig;','&tau;','&there4;','&theta;','&thetasym;','&thinsp;','&thorn;','&tilde;','&times;','&trade;','&uArr;','&uacute;','&uarr;','&ucirc;','&ugrave;','&uml;','&upsih;','&upsilon;','&uuml;','&weierp;','&xi;','&yacute;','&yen;','&yuml;','&zeta;','&zwj;','&zwnj;');
 $xml = array('&#198;','&#193;','&#194;','&#192;','&#913;','&#197;','&#195;','&#196;','&#914;','&#199;','&#935;','&#8225;','&#916;','&#208;','&#201;','&#202;','&#200;','&#917;','&#919;','&#203;','&#915;','&#205;','&#206;','&#204;','&#921;','&#207;','&#922;','&#923;','&#924;','&#209;','&#925;','&#338;','&#211;','&#212;','&#210;','&#937;','&#927;','&#216;','&#213;','&#214;','&#934;','&#928;','&#8243;','&#936;','&#929;','&#352;','&#931;','&#222;','&#932;','&#920;','&#218;','&#219;','&#217;','&#933;','&#220;','&#926;','&#221;','&#376;','&#918;','&#225;','&#226;','&#180;','&#230;','&#224;','&#8501;','&#945;','&#38;','&#8743;','&#8736;','&#39;','&#229;','&#8776;','&#227;','&#228;','&#8222;','&#946;','&#166;','&#8226;','&#8745;','&#231;','&#184;','&#162;','&#967;','&#710;','&#9827;','&#8773;','&#169;','&#8629;','&#8746;','&#164;','&#8659;','&#8224;','&#8595;','&#176;','&#948;','&#9830;','&#247;','&#233;','&#234;','&#232;','&#8709;','&#8195;','&#8194;','&#949;','&#8801;','&#951;','&#240;','&#235;','&#8364;','&#8707;','&#402;','&#8704;','&#189;','&#188;','&#190;','&#8260;','&#947;','&#8805;','&#62;','&#8660;','&#8596;','&#9829;','&#8230;','&#237;','&#238;','&#161;','&#236;','&#8465;','&#8734;','&#8747;','&#953;','&#191;','&#8712;','&#239;','&#954;','&#8656;','&#955;','&#9001;','&#171;','&#8592;','&#8968;','&#8220;','&#8804;','&#8970;','&#8727;','&#9674;','&#8206;','&#8249;','&#8216;','&#60;','&#175;','&#8212;','&#181;','&#183;','&#8722;','&#956;','&#8711;','&#160;','&#8211;','&#8800;','&#8715;','&#172;','&#8713;','&#8836;','&#241;','&#957;','&#243;','&#244;','&#339;','&#242;','&#8254;','&#969;','&#959;','&#8853;','&#8744;','&#170;','&#186;','&#248;','&#245;','&#8855;','&#246;','&#182;','&#8706;','&#8240;','&#8869;','&#966;','&#960;','&#982;','&#177;','&#163;','&#8242;','&#8719;','&#8733;','&#968;','&#34;','&#8658;','&#8730;','&#9002;','&#187;','&#8594;','&#8969;','&#8221;','&#8476;','&#174;','&#8971;','&#961;','&#8207;','&#8250;','&#8217;','&#8218;','&#353;','&#8901;','&#167;','&#173;','&#963;','&#962;','&#8764;','&#9824;','&#8834;','&#8838;','&#8721;','&#185;','&#178;','&#179;','&#8835;','&#8839;','&#223;','&#964;','&#8756;','&#952;','&#977;','&#8201;','&#254;','&#732;','&#215;','&#8482;','&#8657;','&#250;','&#8593;','&#251;','&#249;','&#168;','&#978;','&#965;','&#252;','&#8472;','&#958;','&#253;','&#165;','&#255;','&#950;','&#8205;','&#8204;');
 $str = str_replace($html,$xml,$str);
 $str = str_ireplace($html,$xml,$str);
 return $str;
}

//General Functions

function CheckVar($var, $permittedArray)
{
 if (isset($var))
 {
  foreach ($permittedArray as $value)
  {
   if (strtolower($var) == strtolower($value))
   {
    return strtolower($var);
   }
  }
 }
}
function CheckVars($vars)
{
 //$filesArray = CleanArray($vars["files"]);
 $vars = CleanArray($vars);
 //$vars["files"] = $filesArray;
 $defaultArray['filename'] = "TVGuide.xml";
 $defaultArray['days'] = 7;
 $defaultArray['compress'] = "none";
 $defaultArray['icons'] = "yes";
 $defaultArray['offset'] = "";
 
 if (isset($vars["channels"]) and isset($vars["provider"]))
 {
  $providerResult = mysql_query("SELECT " . $vars["provider"] . "_number FROM xmltvChannels WHERE " . $vars["provider"] . "_number IS NOT NULL"); //or die(mysql_error());
  if (mysql_num_rows($providerResult) > 0)
  {
   $returnArray['raw']['provider'] = $vars["provider"];
   $channelsArray = explode(",", $vars["channels"]);
   foreach ($channelsArray as $channel)
   {
    //$channelArray = explode("-", $channel);
	$channelArray[0] = "All";
	$channelArray[1] = $channel;
	if ($channelArray[0] <> '' AND $channelArray[0] <> NULL AND $channelArray[1] <> '' AND $channelArray[1] <> NULL)
	{
	 $channelResult = mysql_query("SELECT " . $vars["provider"] . "_number FROM xmltvChannels WHERE " . $channelArray[0] . "_id='$channel'") or die(mysql_error());
     if (mysql_num_rows($channelResult) > 0)
     {
	  $channelRow = mysql_fetch_assoc($channelResult);
      //$returnArray['raw']['channels'][$channelRow[$returnArray['raw']['provider'] . '_number']] = $channel;
	  $returnArray['raw']['channels'][$channel] = $channel;
     }
	}
   }
   if (isset($returnArray['raw']['channels']))
   {
	//asort($returnArray['raw']['channels']);
	ksort($returnArray['raw']['channels']);
	$returnArray['raw']['filename'] = $defaultArray['filename'];
	if (isset($vars["filename"]))
    {
	 if (basename($vars["filename"]) <> "")
	 {
      $returnArray['raw']['filename'] = basename($vars["filename"]);
	 }
    }
    $returnArray['raw']['days'] = $defaultArray['days'];
    if (isset($vars["days"]))
    {
     $vars["days"] = (int)$vars["days"];
	 if (is_int($vars["days"]))
     {
      if ($vars["days"] >= 0 AND $vars["days"] < $defaultArray['days'])
      {
       $returnArray['raw']['days'] = $vars["days"];
       $returnArray['url']['days'] = $returnArray['raw']['days'];
      }
     }
    }
    $archiveVars = array('archive', 'dtds', 'channelinfo', 'icons', 'ratingicons');
	$noProcess = FALSE;
	if (isset($vars["archive"]))
	{
	 switch ($vars["archive"])
	 {
	  case 'zip':
	   $noProcess = TRUE;
 	   foreach ($archiveVars as $archiveVar)
	   {
		unset($returnArray['raw'][$archiveVar]);
	   }
	   $returnArray['raw']['archive'] = $vars["archive"];
	  break;
	  case 'none':
	   $returnArray['raw']['archive'] = $vars["archive"];
	  break;
	 }
	}
	if ($returnArray['raw']['archive'] == 'none')
	{
     $returnArray['url']['archive'] = $returnArray['raw']['archive'];
	 /*if (isset($vars["files"]))
	 {
	  foreach ($vars["files"] as $filesOptions)
	  {
	   switch ($filesOptions)
	   {
	    case 'channelinfo':
	     $returnArray['raw'][channelinfo] = TRUE;
	    break;
		case 'dtds':
	     $returnArray['raw'][dtds] = TRUE;
	    break;
	    case 'ratingicons':
	     $returnArray['raw'][ratingicons] = TRUE;
	    break;
		case 'newxml':
	     $returnArray['raw']['newxml'] = TRUE;
	    break;
       }
	  }
	 }*/
	 /*foreach (array('channelinfo', 'dtds', 'ratingicons') as $filesOption)
	 if ($returnArray['raw'][$filesOption] == TRUE)
	 {
	  $returnArray['url'][files] .= $filesOption . ',';
	 }
	 $returnArray['url'][files] = rtrim($returnArray['url'][files], ",");
	 $returnArray['url']['icons'] = 'none';
	 */
	 if (isset($vars["icons"]))
	 {
	  switch ($vars["icons"])
	  {
	   case 'small':
	   case 'large':
	    $returnArray['raw']['icons'] = $vars["icons"];
		$returnArray['url']['icons'] = $returnArray['raw']['icons'];
	   break;
      }
	 }
	 $returnArray['raw']['links'] = 'remote';
	 if (isset($vars["links"]))
	 {
	  switch ($vars["links"])
	  {
	   case 'local':
	   case 'remote':
	   case 'xmltv':
	    $returnArray['raw']['links'] = $vars["links"];
	   break;
	  }   
	 }
	 $returnArray['url']['links'] = $returnArray['raw']['links'];
	}
	else
	{
	 $returnArray['url']['archive'] = $returnArray['raw']['archive'];
	}
	
    $returnArray['raw']['offset'] = "";
    if (isset($vars["offset"]))
    {
     switch ($vars["offset"])
     {
      case 'NZT':
      case 'NZST':
      case 'NZDT':
       $returnArray['url']['offset'] = $vars["offset"];
	   $returnArray['raw']['offset'] = " " . $returnArray['url']['offset'];
      break;
	  default:
	   $timeOffset = (int)$vars["offset"];
	   if (is_int($timeOffset))
	   {
	    if ($timeOffset >= -2359 AND $timeOffset <= 2359 AND $timeOffset % 100 < 60 AND $timeOffset <> 0)
        {
		 $returnArray['url']['offset'] = (string)$timeOffset;
		 if ($timeOffset > -1)
		 {
		  $returnArray['url']['offset'] = '+' . str_pad(ltrim($returnArray['url']['offset'], "+"), 4, "0", STR_PAD_LEFT);
		 }
		 else
		 {
		  $returnArray['url']['offset'] = '-' . str_pad(ltrim($returnArray['url']['offset'], "-"), 4, "0", STR_PAD_LEFT);
		 }
		 $returnArray['raw']['offset'] = " " . $returnArray['url']['offset'];
		}
	   }
	  break;
     }
    }
	$returnArray['url']['provider'] = $vars["provider"];
    $returnArray['url']['channels'] = implode(",", $returnArray['raw']['channels']);
    return $returnArray;
   }
  }
 }
}

function GrabTelstraID($url)
{
 preg_match('~cc=(.*?)&~ism', $url, $channelid);
 return $channelid[1];
}

function connectToDB()
{
 mysql_connect(databaseHost, databaseUsername, databasePassword) or die(mysql_error());
 mysql_select_db(databaseName) or die(mysql_error());
}

function strstrb($h,$n){
 return array_shift(explode($n,$h,2));
}

// Generic Functions

function FixTime($time)
{
 if (substr($time, 0, 2) == '0:')
 {
  $time = '0' . $time;
 }
 if (substr($time, 0, 3) == '00:')
 {
  $time = substr_replace($time, '12', 0, 2);
 }
 return $time;
}

function FreeViewToSQLTime($timeString)
{
 $timeArray = explode(' ', $timeString);
 $timeDateParts = explode('/', $timeArray[0]);
 return $timeDateParts[2] . '-' . $timeDateParts[1] . '-' . $timeDateParts[0] . ' ' . $timeArray[1];
}

function CurrentURL() //From http://www.webcheatsheet.com/PHP/get_current_page_url.php
{
 $pageURL = 'http';
 if (isset($_SERVER["HTTPS"]))
 {
  if ($_SERVER["HTTPS"] == "on") {$pageURL .= "s";}
 }
 $pageURL .= "://";
 if ($_SERVER["SERVER_PORT"] != "80") {
  $pageURL .= $_SERVER["SERVER_NAME"].":".$_SERVER["SERVER_PORT"].$_SERVER["REQUEST_URI"];
 } else {
  $pageURL .= $_SERVER["SERVER_NAME"].$_SERVER["REQUEST_URI"];
 }
 return $pageURL;
}

function GetFileLastLine($url) //Get the last line of a file
{
 $lines = file($url); //Load file into array
 return $lines[count($lines) - 1]; //Return last line
}


?>