<?
function doSomething( &$arg )
{
    $return = $arg;
    $arg += 1;
    return $return;
}

$a = 3;
$b = doSomething( $a );

echo $a;
echo '\n';
echo $b;
?>