<?php
##########################################################################
#                                                                        #
#                                                                        #
#                                                                        #
#                                                                        #
##########################################################################

//\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\
// Adds file header to the tar file, it is used before adding file content.
// f: file resource (provided by eg. fopen)
// phisfn: path to file
// archfn: path to file in archive, directory names must be followed by '/'
//\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\
function TarAddHeader($f,$phisfn,$archfn)
{
    $info=stat($phisfn);
    $ouid=sprintf("%6s ", decoct($info[4]));
    $ogid=sprintf("%6s ", decoct($info[5]));
    $omode=sprintf("%6s ", decoct(fileperms($phisfn)));
    $omtime=sprintf("%11s", decoct(filemtime($phisfn)));
    if (@is_dir($phisfn))
    {
         $type="5";
         $osize=sprintf("%11s ", decoct(0));
    }
    else
    {
         $type='';
         $osize=sprintf("%11s ", decoct(filesize($phisfn)));
         clearstatcache();
    }
    $dmajor = '';
    $dminor = '';
    $gname = '';
    $linkname = '';
    $magic = '';
    $prefix = '';
    $uname = '';
    $version = '';
    $chunkbeforeCS=pack("a100a8a8a8a12A12",$archfn, $omode, $ouid, $ogid, $osize, $omtime);
    $chunkafterCS=pack("a1a100a6a2a32a32a8a8a155a12", $type, $linkname, $magic, $version, $uname, $gname, $dmajor, $dminor ,$prefix,'');

    $checksum = 0;
    for ($i=0; $i<148; $i++) $checksum+=ord(substr($chunkbeforeCS,$i,1));
    for ($i=148; $i<156; $i++) $checksum+=ord(' ');
    for ($i=156, $j=0; $i<512; $i++, $j++)    $checksum+=ord(substr($chunkafterCS,$j,1));

    fwrite($f,$chunkbeforeCS,148);
    $checksum=sprintf("%6s ",decoct($checksum));
    $bdchecksum=pack("a8", $checksum);
    fwrite($f,$bdchecksum,8);
    fwrite($f,$chunkafterCS,356);
    return true;
}
//\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/
// Writes file content to the tar file must be called after a TarAddHeader
// f:file resource provided by fopen
// phisfn: path to file
//\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/
function TarWriteContents($f,$phisfn)
{
    if (@is_dir($phisfn))
    {
        return;
    }
    else
    {
        $size=filesize($phisfn);
        $padding=$size % 512 ? 512-$size%512 : 0;
        $f2=fopen($phisfn,"rb");
        while (!feof($f2)) fwrite($f,fread($f2,1024*1024));
        $pstr=sprintf("a%d",$padding);
        fwrite($f,pack($pstr,''));
    }
}
//\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/
// Adds 1024 byte footer at the end of the tar file
// f: file resource
//\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/\/
function TarAddFooter($f)
{
    fwrite($f,pack('a1024',''));
}

?>