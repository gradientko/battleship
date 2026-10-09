# battleship

A turn-based Battleship game written in pure PHP. There is **no JavaScript**.

## Features

- 8×8 player and enemy boards
- randomized fleets
- ships cannot overlap or touch
- hit / miss detection
- sunk ship tracking
- simple hunt-mode computer AI
- wins, losses and win rate
- best winning turn count
- CSRF protection
- PHP sessions
- POST / Redirect / GET
- no framework
- no database
- no JavaScript

## Fleet

```text
Cruiser       3
Destroyer A   2
Destroyer B   2
Patrol A      1
Patrol B      1
```

## Run

Requires PHP 8.1+.

```bash
php -S localhost:8000
```

Open `http://localhost:8000`.

## AI

The computer normally chooses an untested cell randomly. After a hit it prefers untested neighboring cells, giving it a small hunt mode.

## Project structure

```text
battleship/
├── index.php
├── functions.php
├── style.css
└── README.md
```

GitHub Pages cannot execute PHP.

## License

MIT
