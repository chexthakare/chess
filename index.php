<?php
header('Content-Type: text/html; charset=utf-8');
// Self-contained PHP + HTML/CSS/JS 3D chess board demo.
// No external libraries, Bootstrap, or jQuery are required.
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>3D Chess Board</title>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/chess.js/0.10.3/chess.min.js"></script>
    <style>
        :root {
            color-scheme: dark;
            --bg-color: #312e2b;
            --board-light: #eeeed2;
            --board-dark: #769656;
            --highlight: rgba(255, 255, 51, 0.5);
            --legal-dot: rgba(0, 0, 0, 0.15);
            --legal-ring: rgba(0, 0, 0, 0.15);
            --text-main: #fff;
            --panel-bg: #262421;
        }

        * { box-sizing: border-box; }

        body {
            margin: 0;
            min-height: 100vh;
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif;
            background-color: var(--bg-color);
            color: var(--text-main);
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .wrap {
            display: flex;
            gap: 30px;
            padding: 20px;
            max-width: 1200px;
            width: 100%;
            align-items: flex-start;
            flex-wrap: wrap;
            justify-content: center;
        }

        .board-container {
            flex: 0 0 auto;
        }

        .board {
            position: relative;
            width: min(90vw, 700px);
            aspect-ratio: 1;
            background: var(--board-light);
            border-radius: 4px;
            overflow: hidden;
            box-shadow: 0 4px 12px rgba(0,0,0,0.5);
        }

        .square {
            position: absolute;
            width: 12.5%;
            height: 12.5%;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .square.dark {
            background-color: var(--board-dark);
        }

        .square.light {
            background-color: var(--board-light);
        }

        .square.selected::after {
            content: '';
            position: absolute;
            inset: 0;
            background-color: var(--highlight);
            pointer-events: none;
        }

        .square.legal::before {
            content: '';
            position: absolute;
            width: 30%;
            height: 30%;
            border-radius: 50%;
            background-color: var(--legal-dot);
            z-index: 10;
            pointer-events: none;
        }

        .square.legal.has-piece::before {
            width: 85%;
            height: 85%;
            background-color: transparent;
            border: 6px solid var(--legal-ring);
        }

        .piece {
            position: relative;
            width: 100%;
            height: 100%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: min(10vw, 75px);
            cursor: grab;
            user-select: none;
            z-index: 15;
            text-shadow: 0 2px 4px rgba(0,0,0,0.4);
            touch-action: none;
            color: transparent;
        }

        .piece:active {
            cursor: grabbing;
        }

        .piece.w { text-shadow: 0 0 0 #fff, 0 1px 2px rgba(0,0,0,0.8); }
        .piece.b { text-shadow: 0 0 0 #000, 0 1px 2px rgba(255,255,255,0.8); }

        .drag-ghost {
            position: fixed;
            z-index: 9999;
            pointer-events: none;
        }

        .drag-ghost .piece {
            transform: scale(1.15);
            text-shadow: 0 0 0 inherit, 0 15px 15px rgba(0,0,0,0.5);
        }
        
        .drag-ghost .piece.w { text-shadow: 0 0 0 #fff, 0 15px 15px rgba(0,0,0,0.5); }
        .drag-ghost .piece.b { text-shadow: 0 0 0 #000, 0 15px 15px rgba(0,0,0,0.5); }

        .panel {
            flex: 1;
            min-width: 300px;
            background-color: var(--panel-bg);
            border-radius: 8px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 15px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.3);
        }

        h1 {
            margin: 0;
            font-size: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .status {
            padding: 12px;
            background: rgba(255,255,255,0.05);
            border-radius: 6px;
            font-weight: bold;
        }

        .hud {
            display: flex;
            flex-direction: column;
            gap: 10px;
        }

        .player-tag {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 10px;
            background: rgba(0,0,0,0.2);
            border-radius: 6px;
        }

        .avatar {
            width: 40px;
            height: 40px;
            border-radius: 4px;
            background: #555;
            display: grid;
            place-items: center;
            font-size: 20px;
        }

        .controls {
            margin-top: auto;
            display: flex;
            gap: 10px;
        }

        button {
            flex: 1;
            padding: 12px;
            border: none;
            border-radius: 6px;
            background-color: #4a4846;
            color: white;
            font-weight: bold;
            cursor: pointer;
            transition: background 0.2s;
        }

        button:hover {
            background-color: #5c5957;
        }

        @media (max-width: 700px) {
            .wrap { padding: 10px; }
            h1 { font-size: 1.2rem; }
            button { padding: 7px 12px; }
        }
    </style>
</head>
<body>
    <div class="wrap">
        <div class="board-container">
            <div id="board" class="board" aria-label="Chess board"></div>
        </div>

        <div class="panel">
            <h1>&#9822; Play Computer</h1>
            
            <div class="hud">
                <div class="player-tag">
                    <div class="avatar">&#129302;</div>
                    <div>Computer (Score: <span id="computerScore">0</span>)</div>
                </div>
                
                <div class="player-tag">
                    <div class="avatar">&#128100;</div>
                    <div>You (Score: <span id="playerScore">0</span>)</div>
                </div>
            </div>

            <div id="status" class="status">White to move.</div>

            <div class="controls">
                <button id="newGame" type="button">New Game</button>
                <button id="flipBoard" type="button">Flip Board</button>
            </div>
        </div>
    </div>

    <script>
        (() => {
            const boardEl = document.getElementById('board');
            const statusEl = document.getElementById('status');
            const playerScoreEl = document.getElementById('playerScore');
            const computerScoreEl = document.getElementById('computerScore');
            
            const pieceIcons = {
                w: { k: '&#9812;', q: '&#9813;', r: '&#9814;', b: '&#9815;', n: '&#9816;', p: '&#9817;' },
                b: { k: '&#9818;', q: '&#9819;', r: '&#9820;', b: '&#9821;', n: '&#9822;', p: '&#9823;' }
            };
            const pieceValues = { p: 1, n: 3, b: 3, r: 5, q: 9, k: 0 };

            let game = new Chess();
            let selectedSquare = null;
            let legalMoves = [];
            let flipped = false;
            let playerScore = 0;
            let computerScore = 0;
            let dragState = null;

            function playSound(type) {
                try {
                    const ctx = new (window.AudioContext || window.webkitAudioContext)();
                    const oscillator = ctx.createOscillator();
                    const gainNode = ctx.createGain();
                    const tones = {
                        select: [440, 660], move: [523, 392], capture: [660, 220], computer: [330, 440]
                    };
                    const [startFreq, endFreq] = tones[type] || [440, 520];
                    oscillator.type = 'sine';
                    oscillator.frequency.setValueAtTime(startFreq, ctx.currentTime);
                    oscillator.frequency.exponentialRampToValueAtTime(endFreq, ctx.currentTime + 0.16);
                    gainNode.gain.setValueAtTime(0.03, ctx.currentTime);
                    gainNode.gain.exponentialRampToValueAtTime(0.001, ctx.currentTime + 0.2);
                    oscillator.connect(gainNode);
                    gainNode.connect(ctx.destination);
                    oscillator.start(); oscillator.stop(ctx.currentTime + 0.2);
                    setTimeout(() => ctx.close(), 250);
                } catch (e) {}
            }

            function updateScoreDisplay() {
                playerScoreEl.textContent = playerScore;
                computerScoreEl.textContent = computerScore;
            }

            function updateTurnStatus() {
                if (game.in_checkmate()) {
                    updateStatus('💥 ' + (game.turn() === 'w' ? 'White' : 'Black') + ' is checkmated!');
                } else if (game.in_draw() || game.in_stalemate() || game.in_threefold_repetition()) {
                    updateStatus('🤝 Draw!');
                } else if (game.in_check()) {
                    updateStatus('⚠️ ' + (game.turn() === 'w' ? 'White' : 'Black') + ' is in check!');
                } else {
                    updateStatus(game.turn() === 'w' ? '✨ White to move.' : '🤖 Black to move.');
                }
            }

            function updateStatus(message) {
                statusEl.textContent = message;
            }

            function renderBoard() {
                boardEl.innerHTML = '';
                const boardArray = game.board();

                for (let r = 0; r < 8; r++) {
                    for (let c = 0; c < 8; c++) {
                        const displayRow = flipped ? 7 - r : r;
                        const displayCol = flipped ? 7 - c : c;
                        const squareName = ['a', 'b', 'c', 'd', 'e', 'f', 'g', 'h'][c] + (8 - r);
                        const piece = boardArray[r][c];
                        const isLight = (r + c) % 2 === 0;

                        const square = document.createElement('div');
                        square.className = 'square ' + (isLight ? 'light' : 'dark');
                        square.dataset.square = squareName;
                        square.style.left = (displayCol * 12.5) + '%';
                        square.style.top = (displayRow * 12.5) + '%';
                        square.addEventListener('click', handleSquareClick);

                        if (selectedSquare === squareName) {
                            square.classList.add('selected');
                        }

                        if (legalMoves.includes(squareName)) {
                            square.classList.add('legal');
                            if (piece) square.classList.add('has-piece');
                        }

                        if (piece) {
                            const pieceEl = document.createElement('div');
                            pieceEl.className = 'piece ' + piece.color + ' ' + piece.type;
                            pieceEl.dataset.square = squareName;
                            pieceEl.innerHTML = pieceIcons[piece.color][piece.type];

                            pieceEl.addEventListener('pointerdown', handlePiecePointerDown);
                            pieceEl.addEventListener('click', event => event.stopPropagation());
                            square.appendChild(pieceEl);
                        }

                        boardEl.appendChild(square);
                    }
                }
            }

            function createDragGhost(pieceObj, squareName) {
                const ghost = document.createElement('div');
                ghost.className = 'drag-ghost';
                ghost.dataset.square = squareName;

                const pieceEl = document.createElement('div');
                pieceEl.className = 'piece ' + pieceObj.color + ' ' + pieceObj.type;
                pieceEl.innerHTML = pieceIcons[pieceObj.color][pieceObj.type];
                
                ghost.appendChild(pieceEl);
                document.body.appendChild(ghost);
                return ghost;
            }

            function handlePiecePointerDown(event) {
                const square = event.currentTarget.dataset.square;
                const piece = game.get(square);
                if (!piece || piece.color !== game.turn()) return;

                event.preventDefault();
                selectedSquare = square;
                const moves = game.moves({ square: square, verbose: true });
                legalMoves = moves.map(m => m.to);
                
                renderBoard();
                playSound('select');

                const pieceEl = event.currentTarget;
                const pieceRect = pieceEl.getBoundingClientRect();
                
                const ghost = createDragGhost(piece, square);
                ghost.style.width = pieceRect.width + 'px';
                ghost.style.height = pieceRect.height + 'px';
                ghost.style.left = pieceRect.left + 'px';
                ghost.style.top = pieceRect.top + 'px';
                
                pieceEl.style.opacity = '0.3';

                const offsetX = event.clientX - pieceRect.left;
                const offsetY = event.clientY - pieceRect.top;

                dragState = { pieceEl, ghost, fromSquare: square, offsetX, offsetY };
                boardEl.classList.add('drag-active');

                const onPointerMove = (moveEvent) => {
                    if (!dragState) return;
                    dragState.ghost.style.left = (moveEvent.clientX - dragState.offsetX) + 'px';
                    dragState.ghost.style.top = (moveEvent.clientY - dragState.offsetY) + 'px';
                };

                const onPointerUp = (upEvent) => {
                    if (!dragState) return;
                    
                    const ghost = dragState.ghost;
                    
                    ghost.style.display = 'none';
                    const targetEl = document.elementFromPoint(upEvent.clientX, upEvent.clientY)?.closest('.square');
                    ghost.style.display = '';
                    
                    const targetSquare = targetEl?.dataset.square;
                    const fromSquare = dragState.fromSquare;

                    document.removeEventListener('pointermove', onPointerMove);
                    document.removeEventListener('pointerup', onPointerUp);
                    boardEl.classList.remove('drag-active');

                    if (targetSquare && legalMoves.includes(targetSquare)) {
                        completeMove(fromSquare, targetSquare);
                    } else {
                        selectedSquare = null;
                        legalMoves = [];
                        renderBoard();
                        updateTurnStatus();
                    }
                    
                    ghost.remove();
                    dragState = null;
                };

                document.addEventListener('pointermove', onPointerMove);
                document.addEventListener('pointerup', onPointerUp);
            }

            function handleSquareClick(event) {
                const square = event.currentTarget.dataset.square;
                const piece = game.get(square);

                if (selectedSquare && legalMoves.includes(square)) {
                    completeMove(selectedSquare, square);
                    return;
                }

                if (piece && piece.color === game.turn()) {
                    selectedSquare = square;
                    const moves = game.moves({ square: square, verbose: true });
                    legalMoves = moves.map(m => m.to);
                    renderBoard();
                    playSound('select');
                } else {
                    selectedSquare = null;
                    legalMoves = [];
                    renderBoard();
                }
            }

            function completeMove(fromSquare, toSquare) {
                const moveResult = game.move({ from: fromSquare, to: toSquare, promotion: 'q' });
                if (moveResult) {
                    if (moveResult.captured) {
                        const points = pieceValues[moveResult.captured] || 0;
                        if (moveResult.color === 'w') playerScore += points;
                        else computerScore += points;
                        updateScoreDisplay();
                        playSound('capture');
                    } else {
                        playSound('move');
                    }
                }
                
                selectedSquare = null;
                legalMoves = [];
                renderBoard();
                updateTurnStatus();

                if (!game.game_over() && game.turn() === 'b') {
                    setTimeout(computerMove, 500);
                }
            }

            function computerMove() {
                if (game.turn() !== 'b' || game.game_over()) return;

                const moves = game.moves({ verbose: true });
                if (moves.length === 0) return;
                
                const move = moves[Math.floor(Math.random() * moves.length)];
                
                const moveResult = game.move(move);
                if (moveResult && moveResult.captured) {
                    computerScore += pieceValues[moveResult.captured] || 0;
                    updateScoreDisplay();
                    playSound('capture');
                } else {
                    playSound('computer');
                }
                
                renderBoard();
                updateTurnStatus();
            }

            document.getElementById('newGame').addEventListener('click', () => {
                game.reset();
                playerScore = 0;
                computerScore = 0;
                selectedSquare = null;
                legalMoves = [];
                updateScoreDisplay();
                renderBoard();
                updateTurnStatus();
            });
            
            document.getElementById('flipBoard').addEventListener('click', () => {
                flipped = !flipped;
                renderBoard();
            });

            renderBoard();
            updateTurnStatus();
        })();
    </script>
</body>
</html>

