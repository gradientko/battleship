<?php
declare(strict_types=1);
session_start();
require_once __DIR__.'/functions.php';

$_SESSION['battleship_stats'] ??= ['games'=>0,'wins'=>0,'losses'=>0,'best_turns'=>null];
$_SESSION['battleship_game'] ??= createGame();
$_SESSION['csrf_token'] ??= bin2hex(random_bytes(16));

if($_SERVER['REQUEST_METHOD']==='POST') {
    $token=$_POST['csrf_token']??'';
    if(is_string($token) && hash_equals($_SESSION['csrf_token'],$token)) {
        $action=$_POST['action']??'';
        if($action==='new') {
            $_SESSION['battleship_game']=createGame();
            header('Location: index.php'); exit;
        }

        $g=$_SESSION['battleship_game'];
        if($action==='fire' && $g['status']==='playing') {
            $x=filter_input(INPUT_POST,'x',FILTER_VALIDATE_INT);
            $y=filter_input(INPUT_POST,'y',FILTER_VALIDATE_INT);
            if($x!==false && $x!==null && $y!==false && $y!==null) {
                playerFire($g,$x,$y);
                if($g['status']!=='playing' && empty($g['settled'])) {
                    $_SESSION['battleship_stats']['games']++;
                    if($g['status']==='won') {
                        $_SESSION['battleship_stats']['wins']++;
                        $best=$_SESSION['battleship_stats']['best_turns'];
                        if($best===null || $g['turns']<$best) $_SESSION['battleship_stats']['best_turns']=$g['turns'];
                    } else $_SESSION['battleship_stats']['losses']++;
                    $g['settled']=true;
                }
                $_SESSION['battleship_game']=$g;
            }
            header('Location: index.php'); exit;
        }
    }
}

$g=$_SESSION['battleship_game']; $s=$_SESSION['battleship_stats'];
$winRate=$s['games']?round($s['wins']/$s['games']*100):0;
?>
<!doctype html>
<html lang="en">
<head>
<meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>Battleship — Pure PHP</title>
<link rel="stylesheet" href="style.css">
</head>
<body>
<main class="shell">
<header class="hero">
<div><span class="eyebrow">PURE PHP BATTLESHIP</span><h1>Sink the fleet.</h1>
<p>Pick a target, fire, and survive the enemy response. Every move is processed on the server.</p></div>
<form method="post"><input type="hidden" name="csrf_token" value="<?=e($_SESSION['csrf_token'])?>">
<input type="hidden" name="action" value="new"><button class="link">New battle</button></form>
</header>

<section class="stats">
<div><span>Turns</span><strong><?=$g['turns']?></strong></div>
<div><span>Wins</span><strong><?=$s['wins']?></strong></div>
<div><span>Win rate</span><strong><?=$winRate?>%</strong></div>
<div><span>Best</span><strong><?=$s['best_turns']??'—'?></strong></div>
</section>

<section class="game">
<div class="game-head"><div><span class="eyebrow">BATTLE LOG</span><h2><?=e($g['message'])?></h2></div>
<span class="pill <?=e($g['status'])?>"><?=strtoupper(e($g['status']))?></span></div>

<div class="boards">
<section><span class="eyebrow">ENEMY WATERS</span><h3>Choose a target</h3>
<div class="board">
<div></div><?php for($x=0;$x<SIZE;$x++): ?><div class="axis"><?=chr(65+$x)?></div><?php endfor; ?>
<?php for($y=0;$y<SIZE;$y++): ?><div class="axis"><?=$y+1?></div>
<?php for($x=0;$x<SIZE;$x++): $c=enemyCell($g,$x,$y); ?>
<?php if($c==='unknown' && $g['status']==='playing'): ?>
<form method="post" class="cell-form"><input type="hidden" name="csrf_token" value="<?=e($_SESSION['csrf_token'])?>">
<input type="hidden" name="action" value="fire"><input type="hidden" name="x" value="<?=$x?>"><input type="hidden" name="y" value="<?=$y?>">
<button class="cell unknown" title="<?=e(label($x,$y))?>">·</button></form>
<?php else: ?><div class="cell <?=e($c)?>"><?=$c==='hit'?'×':($c==='miss'?'•':'')?></div><?php endif; ?>
<?php endfor; endfor; ?>
</div></section>

<section><span class="eyebrow">YOUR WATERS</span><h3>Your fleet</h3>
<div class="board">
<div></div><?php for($x=0;$x<SIZE;$x++): ?><div class="axis"><?=chr(65+$x)?></div><?php endfor; ?>
<?php for($y=0;$y<SIZE;$y++): ?><div class="axis"><?=$y+1?></div>
<?php for($x=0;$x<SIZE;$x++): $c=playerCell($g,$x,$y); ?>
<div class="cell <?=e($c)?>"><?=$c==='hit'?'×':($c==='miss'?'•':($c==='ship'?'■':''))?></div>
<?php endfor; endfor; ?>
</div></section>
</div>

<?php if($g['status']!=='playing'): ?>
<form method="post" class="again"><input type="hidden" name="csrf_token" value="<?=e($_SESSION['csrf_token'])?>">
<input type="hidden" name="action" value="new"><button>Play again</button></form>
<?php endif; ?>
</section>

<section class="panels">
<section class="panel"><span class="eyebrow">ENEMY FLEET</span>
<?php foreach($g['enemy_ships'] as $ship): ?><div class="fleet <?=$ship['sunk']?'sunk':''?>"><span><?=e($ship['name'])?></span><strong><?=e(shipStatus($ship))?></strong></div><?php endforeach; ?>
</section>
<section class="panel"><span class="eyebrow">YOUR FLEET</span>
<?php foreach($g['player_ships'] as $ship): ?><div class="fleet <?=$ship['sunk']?'sunk':''?>"><span><?=e($ship['name'])?></span><strong><?=e(shipStatus($ship))?></strong></div><?php endforeach; ?>
</section>
<section class="panel"><span class="eyebrow">PURE SERVER SIDE</span>
<p>No JavaScript. Fleet generation, shots, hit detection, AI targeting and statistics are handled by PHP and <code>$_SESSION</code>.</p></section>
</section>

<footer><span>PHP sessions · hunt-mode AI · no JavaScript</span><span>battleship</span></footer>
</main>
</body></html>
