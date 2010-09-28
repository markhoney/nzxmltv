#!/usr/bin/perl

# PERL MODULES
#use DBD::mysql;
use DBI;

# MYSQL CONFIG VARIABLES
$host = "localhost";
$database = "nzxmltv";
$tablename = "Sources";
$user = "nzxmltv";
$pw = "NZXMLTV";

# PERL CONNECT()
$connect = DBI->connect('dbi:mysql:' . $database, $user, $pw) or die "Connection Error: $DBI::errstr\n";

$sql = "select * from " . $tablename;
$sth = $connect->prepare($sql);
$sth->execute or die "SQL Error: $DBI::errstr\n";
while (@row = $sth->fetchrow_array) {
 print "@row\n";
} 