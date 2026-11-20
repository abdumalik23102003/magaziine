<?php
/**
 * @param Integer[] $nums
 * @return Integer[][]
 */
function subsets($nums) {
    $temp = [];
    $answers = [];
    for ($i = 0; $i < count($nums); $i++) {
        for ($j = $i; $j < count($nums); $j++) {
            $temp[] = [];
        }
    }
}
function busiestServers($k, $arrival, $load) {
    $count = array_fill(0, $k, 0);
    $available = array_fill(0, $k, 0);
    for ($i = 0; $i < count($arrival); $i++) {
        $j = $i%$k;
        do {
            if ($available[$j] > $arrival[$i]) {
                $j = ($j + 1) % $k;
                continue;
            }
            $available[$j] = $arrival[$i] + $load[$i];
            $count[$j]++;
            break;
        } while ($i%$k != $j);
    }
    $ans = [];
    $max = max($count);
    for ($i = 0; $i < count($count); $i++) {
        if ($count[$i] == $max) {
            $ans[] = $i;
        }
    }
    return $ans;
}
