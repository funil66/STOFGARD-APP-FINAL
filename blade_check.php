<?php
$content = file_get_contents('/tmp/orcamento.blade.php');
$tags = [];
preg_match_all('/@(if|foreach|for|while|switch|unless|isset|empty)(\s*\(.*?\))?/', $content, $tags_open, PREG_OFFSET_CAPTURE);
preg_match_all('/@(endif|endforeach|endfor|endwhile|endswitch|endunless|endisset|endempty)/', $content, $tags_close, PREG_OFFSET_CAPTURE);

$combined = [];
foreach($tags_open[0] as $t) {
    if(str_contains($t[0], 'empty($')) continue; // skip @empty($var) function 
    if(strpos($t[0], '@empty') === 0 && count(explode(' ', $t[0])) > 1 && strpos($t[0], '(') !== false) continue;
    $combined[$t[1]] = ['type' => 'open', 'tag' => $t[0]];
}
foreach($tags_open[1] as $k => $t) {
    $combined[$tags_open[0][$k][1]] = ['type' => 'open', 'tag' => '@'.$t[0]];
}
foreach($tags_close[1] as $k => $t) {
    $combined[$tags_close[0][$k][1]] = ['type' => 'close', 'tag' => '@'.$t[0]];
}

ksort($combined);

$stack = [];
foreach($combined as $pos => $item) {
    if($item['type'] == 'open') {
        $stack[] = $item['tag'].' at '.$pos;
    } else {
        $last = array_pop($stack);
        $expected = str_replace('end', '', $item['tag']);
        if(strpos($last, $expected) !== 0) {
            echo "Mismatch at $pos: expected close for $last, got {$item['tag']}\n";
        }
    }
}
if(count($stack) > 0) {
    echo "Unclosed tags: ".print_r($stack, true);
} else {
    echo "Balanced.\n";
}
