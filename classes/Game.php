<?php

class Game
{
    private string $player1;
    private string $player2;
    private int $score1 = 0;
    private int $score2 = 0;
    private int $draws = 0;

    public function __construct(string $player1, string $player2)
    {
        $this->player1 = $player1;
        $this->player2 = $player2;
    }

    // تضيف نقطة للفائز (1 = اللاعب الأول، 2 = اللاعب الثاني)
    public function addWin(int $player): void
    {
        if ($player === 1) {
            $this->score1++;
        } elseif ($player === 2) {
            $this->score2++;
        }
    }

    public function addDraw(): void
    {
        $this->draws++;
    }

    public function resetScores(): void
    {
        $this->score1 = 0;
        $this->score2 = 0;
        $this->draws = 0;
    }

    // Getters
    public function getPlayer1(): string { return $this->player1; }
    public function getPlayer2(): string { return $this->player2; }
    public function getScore1(): int { return $this->score1; }
    public function getScore2(): int { return $this->score2; }
    public function getDraws(): int { return $this->draws; }

    // Setters
    public function setPlayer1(string $player1): void { $this->player1 = $player1; }
    public function setPlayer2(string $player2): void { $this->player2 = $player2; }
}