<?php
declare(strict_types=1);

const SIZE = 8;
const FLEET = [
    ['name'=>'Cruiser','size'=>3],
    ['name'=>'Destroyer A','size'=>2],
    ['name'=>'Destroyer B','size'=>2],
    ['name'=>'Patrol A','size'=>1],
    ['name'=>'Patrol B','size'=>1],
];

function emptyBoard(): array {
    return array_fill(0, SIZE, array_fill(0, SIZE, null));
}

function canPlace(array $board, array $cells): bool {
    foreach ($cells as [$x,$y]) {
        if ($board[$y][$x] !== null) return false;
        for ($dy=-1;$dy<=1;$dy++) for ($dx=-1;$dx<=1;$dx++) {
            $nx=$x+$dx; $ny=$y+$dy;
            if ($nx>=0 && $ny>=0 && $nx<SIZE && $ny<SIZE && $board[$ny][$nx] !== null) return false;
        }
    }
    return true;
}

function placeFleet(array &$board): array {
    $ships=[];
    foreach (FLEET as $id=>$def) {
        $placed=false;
        for ($a=0;$a<600 && !$placed;$a++) {
            $h=random_int(0,1)===1; $n=$def['size'];
            $x=random_int(0,$h?SIZE-$n:SIZE-1);
            $y=random_int(0,$h?SIZE-1:SIZE-$n);
            $cells=[];
            for($i=0;$i<$n;$i++) $cells[]=[$x+($h?$i:0),$y+($h?0:$i)];
            if(!canPlace($board,$cells)) continue;
            foreach($cells as [$cx,$cy]) $board[$cy][$cx]=$id;
            $ships[$id]=['name'=>$def['name'],'size'=>$n,'hits'=>0,'sunk'=>false];
            $placed=true;
        }
        if(!$placed) throw new RuntimeException('Could not place fleet');
    }
    return $ships;
}

function createGame(): array {
    $pb=emptyBoard(); $eb=emptyBoard();
    $ps=placeFleet($pb); $es=placeFleet($eb);
    return [
        'player_board'=>$pb,'enemy_board'=>$eb,
        'player_ships'=>$ps,'enemy_ships'=>$es,
        'player_shots'=>[],'enemy_shots'=>[],
        'turns'=>0,'status'=>'playing','settled'=>false,
        'message'=>'Your turn. Fire at the enemy fleet.'
    ];
}

function keyOf(int $x,int $y): string { return "$x,$y"; }
function valid(int $x,int $y): bool { return $x>=0 && $y>=0 && $x<SIZE && $y<SIZE; }
function label(int $x,int $y): string { return chr(ord('A')+$x).($y+1); }

function fleetSunk(array $ships): bool {
    foreach($ships as $ship) if(empty($ship['sunk'])) return false;
    return true;
}

function playerFire(array &$g,int $x,int $y): void {
    if($g['status']!=='playing' || !valid($x,$y)) return;
    $key=keyOf($x,$y);
    if(isset($g['player_shots'][$key])) { $g['message']='You already fired there.'; return; }

    $g['turns']++;
    $id=$g['enemy_board'][$y][$x];
    if($id===null) {
        $g['player_shots'][$key]='miss';
        $g['message']=label($x,$y).': miss.';
    } else {
        $g['player_shots'][$key]='hit';
        $g['enemy_ships'][$id]['hits']++;
        if($g['enemy_ships'][$id]['hits'] >= $g['enemy_ships'][$id]['size']) {
            $g['enemy_ships'][$id]['sunk']=true;
            $g['message']=label($x,$y).': hit. You sank '.$g['enemy_ships'][$id]['name'].'.';
        } else $g['message']=label($x,$y).': hit.';
    }

    if(fleetSunk($g['enemy_ships'])) {
        $g['status']='won'; $g['message']='Enemy fleet destroyed. You win.'; return;
    }
    enemyTurn($g);
}

function enemyTurn(array &$g): void {
    $available=[]; $hunt=[];
    for($y=0;$y<SIZE;$y++) for($x=0;$x<SIZE;$x++) {
        if(!isset($g['enemy_shots'][keyOf($x,$y)])) $available[]=[$x,$y];
    }

    foreach($g['enemy_shots'] as $key=>$result) {
        if($result!=='hit') continue;
        [$hx,$hy]=array_map('intval',explode(',',$key));
        foreach([[1,0],[-1,0],[0,1],[0,-1]] as [$dx,$dy]) {
            $nx=$hx+$dx; $ny=$hy+$dy;
            if(valid($nx,$ny) && !isset($g['enemy_shots'][keyOf($nx,$ny)])) $hunt[]=[$nx,$ny];
        }
    }

    $pool=$hunt ?: $available;
    if(!$pool) return;
    [$x,$y]=$pool[array_rand($pool)];
    $key=keyOf($x,$y); $id=$g['player_board'][$y][$x];

    if($id===null) {
        $g['enemy_shots'][$key]='miss';
        $g['message'].=' Enemy '.label($x,$y).': miss.';
    } else {
        $g['enemy_shots'][$key]='hit';
        $g['player_ships'][$id]['hits']++;
        if($g['player_ships'][$id]['hits'] >= $g['player_ships'][$id]['size']) {
            $g['player_ships'][$id]['sunk']=true;
            $g['message'].=' Enemy '.label($x,$y).': hit, '.$g['player_ships'][$id]['name'].' sunk.';
        } else $g['message'].=' Enemy '.label($x,$y).': hit.';
    }

    if(fleetSunk($g['player_ships'])) {
        $g['status']='lost'; $g['message']='Your fleet has been destroyed.';
    }
}

function enemyCell(array $g,int $x,int $y): string {
    return $g['player_shots'][keyOf($x,$y)] ?? 'unknown';
}
function playerCell(array $g,int $x,int $y): string {
    $shot=$g['enemy_shots'][keyOf($x,$y)] ?? null;
    if($shot==='hit') return 'hit';
    if($shot==='miss') return 'miss';
    return $g['player_board'][$y][$x]===null?'water':'ship';
}
function shipStatus(array $s): string {
    return $s['sunk']?'Sunk':$s['hits'].'/'.$s['size'].' hits';
}
function e(string $v): string {
    return htmlspecialchars($v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');
}
