<?php
$string = 'jolly `roger" includes a <script>alert(0)</script> and a ${whosit}';
$striclean = strtr(
    $string,
    array(
        '\\' => '\\\\',
        "'"  => "\\'",
        '"'  => '\\"',
        "\r" => '\\r',
        "\n" => '\\n',
        '</' => '<\/',
        // see https://html.spec.whatwg.org/multipage/scripting.html#restrictions-for-contents-of-script-elements
        '<!--' => '<\!--',
//        '<s'   => '<\s',
//        '<S'   => '<\S',
        '<s'   => '<&#115;',
        '`' => '\\`',
        '${' => '\\$\\{'
//        "`" => "\\\\`",
//        "\${" => "\\\\\\$\\{"
    )
);
$l1 = strlen($string);
$l2 = strlen($striclean);
$l3 = strlen("\${");
$l4 = strlen('\\'); //1
$l5 = strlen('\\\\'); //2
$l7 = strlen('\\$\\{'); //4

//echo "orig = $string, length = $l1";
//echo "\n";
echo "clean = $striclean, length = $l2";
echo "\n";
