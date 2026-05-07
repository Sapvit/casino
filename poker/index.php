<?php include 'functions.php'; ?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Poker Odds Calculator</title>
    <link rel="stylesheet" href="style.css">
</head>
<body>

<div class="container">
    <h2 style="text-align: center; margin-bottom: 20px;">Poker Odds Calculator</h2>
    
    <div class="top-bar">
        Вид: <select id="game-type"><option>Texas Holdem</option></select>
        &nbsp;&nbsp; Игроков: <input type="number" id="player-count" value="3" min="2" max="9">
    </div>

    <div class="main-layout">
        <div class="main-screen">
            <div class="hand-block" id="hero-hand" style="border-left: 5px solid #3498db;">
                <div class="hand-label">Hero <span id="hero-recommendation" class="hero-recommendation"></span></div>
                <div class="hand-content">
                    <div class="cards-slots" id="hero-slots">
                        <div class="slot" data-type="hero"></div>
                        <div class="slot" data-type="hero"></div>
                    </div>
                    <ul class="probs-list hero-grid" id="hero-probs"></ul>
                </div>
            </div>

            <div class="hand-block" id="board-block" style="border-left: 5px solid #27ae60;">
                <div class="hand-label">Board</div>
                <div class="cards-slots" id="board-slots">
                    <div class="slot" data-type="board"></div>
                    <div class="slot" data-type="board"></div>
                    <div class="slot" data-type="board"></div>
                    <div class="slot" data-type="board"></div>
                    <div class="slot" data-type="board"></div>
                </div>
            </div>

            <div class="deck-container">
                <?php 
                $deck = getDeck();
                $suits = ['clubs' => '♣', 'diamonds' => '♦', 'hearts' => '♥', 'spades' => '♠'];
                foreach ($suits as $key => $symbol): ?>
                    <div class="suit-block">
                        <?php foreach ($deck as $card): 
                            if ($card['suit'] == $key): ?>
                                <img src="Deck/<?= $card['file'] ?>" 
                                     class="deck-card" 
                                     data-card="<?= $card['file'] ?>"
                                     onclick="selectCard(this)">
                            <?php endif; 
                        endforeach; ?>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div id="opponents-area" class="opponents-sidebar">
            </div>
    </div>
</div>

<script>
    const playerCountInput = document.getElementById('player-count');
    const opponentsArea = document.getElementById('opponents-area');

    function formatProb(prob) {
        if (prob === 0) return "0.00%";
        if (prob === 100) return "100.00%";
        
        // Если вероятность мизерная, но не нулевая
        if (prob > 0 && prob < 0.01) return "<0.01%";
        
        // Если вероятность почти 100%, но есть мизерный шанс проиграть
        if (prob > 99.99 && prob < 100) return ">99.99%";
        
        // В остальных случаях обычное округление
        return prob.toFixed(2) + "%";
    }

    function updateOpponents() {
        const count = parseInt(playerCountInput.value) - 1;
        opponentsArea.innerHTML = '';
        for (let i = 1; i <= count; i++) {
            const div = document.createElement('div');
            div.className = 'hand-block opp-mini';
            div.innerHTML = `
                <div class="hand-label">Opponent ${i}</div>
                <div class="hand-content">
                    <div class="cards-slots">
                        <div class="slot small" data-player="${i}"></div>
                        <div class="slot small" data-player="${i}"></div>
                    </div>
                    <ul class="probs-list collapsed" id="opp-${i}-top2"></ul>
                </div>
                <ul class="probs-list expanded-list" id="opp-${i}-full" style="display:none"></ul>
                <button class="expand-btn" onclick="toggleOpponent(${i}, this)">Show all ▼</button>
            `;
            opponentsArea.appendChild(div);
        }
    }

    function toggleOpponent(id, btn) {
        const fullList = document.getElementById(`opp-${id}-full`);
        const top2List = document.getElementById(`opp-${id}-top2`);
        const isHidden = fullList.style.display === 'none';
        
        fullList.style.display = isHidden ? 'grid' : 'none';
        top2List.style.visibility = isHidden ? 'hidden' : 'visible';
        btn.textContent = isHidden ? "Hide ▲" : "Show all ▼";
    }

    function selectCard(el) {
        if (el.classList.contains('selected')) return;
        const file = el.getAttribute('data-card');
        const emptySlot = document.querySelector('.slot:not(.filled)');
        
        if (emptySlot) {
            const img = document.createElement('img');
            img.src = `Deck/${file}`;
            img.onclick = function(e) {
                e.stopPropagation();
                emptySlot.innerHTML = '';
                emptySlot.classList.remove('filled');
                el.classList.remove('selected');
                calculateOdds();
            };
            emptySlot.appendChild(img);
            emptySlot.classList.add('filled');
            el.classList.add('selected');
            calculateOdds();
        }
    }

    async function calculateOdds() {
        const data = {
            hero: Array.from(document.querySelectorAll('.slot[data-type="hero"] img')).map(img => img.src),
            board: Array.from(document.querySelectorAll('.slot[data-type="board"] img')).map(img => img.src),
            opponents: [],
            opponents_count: parseInt(playerCountInput.value) - 1
        };

        for (let i = 1; i <= data.opponents_count; i++) {
            const oppCards = Array.from(document.querySelectorAll(`.slot[data-player="${i}"] img`)).map(img => img.src);
            data.opponents.push(oppCards);
        }

        if (data.hero.length < 2) return;

        try {
            const response = await fetch('engine.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            });
            const result = await response.json();
            window.currentResults = result;

            // Render Hero
            renderProbs('hero-probs', result.hero, true);

            // Render Opponents
            result.opponents.forEach((oppStats, index) => {
                renderProbs(`opp-${index + 1}-top2`, oppStats, false);
                renderProbs(`opp-${index + 1}-full`, oppStats, false);
            });

            // Action
            const equity = result.equity;
            const formattedEquity = formatProb(equity);
            const recSpan = document.getElementById('hero-recommendation');
            if (equity > 45) {
                recSpan.textContent = `(Raise ${formattedEquity})`; recSpan.className = "hero-recommendation text-raise";
            } else if (equity > 20) {
                recSpan.textContent = `(Call ${formattedEquity})`; recSpan.className = "hero-recommendation text-call";
            } else {
                recSpan.textContent = `(Fold ${formattedEquity})`; recSpan.className = "hero-recommendation text-fold";
            }
        } catch (e) { console.error(e); }
    }

    function renderProbs(containerId, stats, isHero) {
        const container = document.getElementById(containerId);
        if (!container || !stats) return;

        const isTop2Only = containerId.includes('top2');
        const sortedStats = [...stats].sort((a, b) => b.prob - a.prob);
        const top2Names = sortedStats.filter(s => s.prob > 0).slice(0, 2).map(s => s.name);

        if (top2Names.length === 0) top2Names.push("High Card", "Pair");

        container.innerHTML = stats.map((s, i) => {
            const heroProb = window.currentResults?.hero[i]?.prob || 0;
            let highlight = "";
            
            if (isHero) {
                const isBest = window.currentResults?.opponents.every(opp => opp[i].prob <= s.prob);
                if (isBest && s.prob > 0) highlight = "best-on-table";
            } else {
                if (s.prob > heroProb) highlight = "better-than-hero";
            }
            
            if (isTop2Only && !top2Names.includes(s.name)) return '';

            return `<li class="prob-item ${highlight}">
                <span>${s.name}</span>
                <strong>${formatProb(s.prob)}</strong>
            </li>`;
        }).join('');
    }

    playerCountInput.addEventListener('change', updateOpponents);
    updateOpponents();
</script>
</body>
</html>