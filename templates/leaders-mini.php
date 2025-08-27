<?php
    $app = $app ?? ($GLOBALS['app'] ?? null);
    $categoryTitle = 'POINTS';
    if ($category == 'goals') { $categoryTitle = 'GOALS';
    } elseif ($category == 'goalsAgainstAverage') { $categoryTitle = 'GAA';
    } elseif ($category == 'wins') { $categoryTitle = 'WINS'; }
?>
<div class="leaders-box">
    <div class="load"></div>
    <div class="player-cont">
        <?php foreach($leaders as $leader) { ?>
            <div class="player" data-player-cont="<?= $leader->id ?>">
                <a id="player-link" data-link="<?= $leader->id ?>" href="#">
                    <div class="info">
                        <div class="headshot">
                            <span class="image" style="background-image: url(<?= $leader->headshot ?>);"></span>
                        </div>
                    </div>
                </a>
            </div>
            <div class="player-text" data-player-text="<?= $leader->id ?>">
                <div class="category"><?= $categoryTitle ?></div>
                <div class="name adv-title"><?= $leader->firstName->default ?> <?= $leader->lastName->default ?></div>
                <div class="more"><div class="tag"><?= $leader->teamAbbrev ?></div> - #<?= $leader->sweaterNumber ?> - <?= positionCodeToName3($leader->position) ?></div>
            </div>
            <div class="value-top" data-player-text="<?= $leader->id ?>">
                <?= is_float($leader->value) ? number_format($leader->value, 2) : $leader->value ?><div><?= $categoryTitle ?></div>
            </div>
        <?php } ?>
    </div>
    <div class="points-cont">
        <?php foreach($leaders as $leader) { ?>

            <div class="points" data-player-id="<?= $leader->id ?>">
                <div class="points-line" data-value="<?= $leader->value ?>"></div>
                <div class="points-name"><a id="player-link" data-link="<?= $leader->id ?>" href="#"><?= $leader->firstName->default ?> <?= $leader->lastName->default ?></a></div>
                <div class="points-value">
                    <?= is_float($leader->value) ? number_format($leader->value, 2) : $leader->value ?>
                </div>
            </div>
        <?php } ?>
    </div>
</div><!-- end leaders-box -->