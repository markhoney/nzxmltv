<form action="" id="sourcecodeform"><fieldset class="codefieldset">

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

include 'libs/nzxmltv.php';
$codefiles = array('libs/nzxmltv.php', 'libs/zipstream.php', 'libs/checkall.js', 'libs/tabber.js', 'download/cache.php', 'gui.php', 'makeurl.php', 'download/index.php', 'gpl.txt');
foreach ($codefiles as $filename)
{
 echo '<h2>' . $filename . '</h2>';
 echo '<div class="codewindow">';
 echo highlight_file($filename);
 echo '</div>';
}
?>

</fieldset></form>
<p>
<a href="http://download.xmltv.co.nz/code.php">Download all source code</a>
</p>