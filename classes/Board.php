<?php

class Board
{
    private array $cells;
    private array $winLine = [];
    private array $locked = []; // كل عنصر: ['r' => , 'c' => , 'ttl' => ]

    public function __construct()
    {
        $this->reset();
    }

    public function reset(): void
    {
        $this->cells = [
            ['', '', ''],
            ['', '', ''],
            ['', '', ''],
        ];
        $this->winLine = [];
        $this->locked = [];
    }

    public function isLocked(int $row, int $col): bool
    {
        foreach ($this->locked as $l) {
            if ($l['r'] === $row && $l['c'] === $col) {
                return true;
            }
        }
        return false;
    }

    public function makeMove(int $row, int $col, string $symbol): bool
    {
        if ($row < 0 || $row > 2 || $col < 0 || $col > 2) {
            return false;
        }
        if ($this->cells[$row][$col] !== '' || $this->isLocked($row, $col)) {
            return false;
        }
        $this->cells[$row][$col] = $symbol;
        return true;
    }

    // ترجع "X" أو "O" أو "Draw" أو "Continue"
    public function checkWinner(): string
    {
        $b = $this->cells;

        $lines = [
            [[0,0], [0,1], [0,2]],
            [[1,0], [1,1], [1,2]],
            [[2,0], [2,1], [2,2]],
            [[0,0], [1,0], [2,0]],
            [[0,1], [1,1], [2,1]],
            [[0,2], [1,2], [2,2]],
            [[0,0], [1,1], [2,2]],
            [[0,2], [1,1], [2,0]],
        ];

        foreach ($lines as $line) {
            [$a, $b2, $c] = $line;
            $v1 = $b[$a[0]][$a[1]];
            $v2 = $b[$b2[0]][$b2[1]];
            $v3 = $b[$c[0]][$c[1]];

            if ($v1 !== '' && $v1 === $v2 && $v2 === $v3) {
                $this->winLine = $line;
                return $v1;
            }
        }

        foreach ($b as $row) {
            if (in_array('', $row, true)) {
                return 'Continue';
            }
        }

        return 'Draw';
    }

    // ===== وضع الفوضى =====

    public function countEmpty(): int
    {
        $n = 0;
        foreach ($this->cells as $row) {
            foreach ($row as $v) {
                if ($v === '') $n++;
            }
        }
        return $n;
    }

    // تنقص مدة كل قفل بحركة، وتشيل المنتهي
    public function tickLocks(): void
    {
        foreach ($this->locked as &$l) {
            $l['ttl']--;
        }
        unset($l);
        $this->locked = array_values(
            array_filter($this->locked, fn($l) => $l['ttl'] > 0)
        );
    }

    // تقفل خانة فاضية عشوائية، وترجع [صف, عمود] أو null
    public function lockRandomEmpty(int $ttl): ?array
    {
        $options = [];
        for ($r = 0; $r < 3; $r++) {
            for ($c = 0; $c < 3; $c++) {
                if ($this->cells[$r][$c] === '' && !$this->isLocked($r, $c)) {
                    $options[] = [$r, $c];
                }
            }
        }
        if (empty($options)) {
            return null;
        }
        $pick = $options[array_rand($options)];
        $this->locked[] = ['r' => $pick[0], 'c' => $pick[1], 'ttl' => $ttl];
        return $pick;
    }

    // تمسح رمز عشوائي، وترجع [صف, عمود] أو null
    public function vanishRandom(): ?array
    {
        $options = [];
        for ($r = 0; $r < 3; $r++) {
            for ($c = 0; $c < 3; $c++) {
                if ($this->cells[$r][$c] !== '') {
                    $options[] = [$r, $c];
                }
            }
        }
        if (empty($options)) {
            return null;
        }
        $pick = $options[array_rand($options)];
        $this->cells[$pick[0]][$pick[1]] = '';
        return $pick;
    }

    public function getLockedCells(): array
    {
        return array_map(fn($l) => [$l['r'], $l['c']], $this->locked);
    }

    public function getCells(): array
    {
        return $this->cells;
    }

    public function getWinLine(): array
    {
        return $this->winLine;
    }
}