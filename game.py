import random
from dataclasses import dataclass, field
from typing import List, Set, Tuple


Coordinate = Tuple[int, int]


@dataclass
class GridAdventureGame:
    size: int = 5
    trap_count: int = 4
    max_actions: int = 15
    trap_penalty: int = 2
    player: Coordinate = field(init=False)
    treasure: Coordinate = field(init=False)
    traps: Set[Coordinate] = field(init=False)
    actions_left: int = field(init=False)

    def __post_init__(self) -> None:
        self.player = (self.size // 2, self.size // 2)
        self.treasure = self._random_empty_cell({self.player})
        self.traps = set()
        occupied = {self.player, self.treasure}
        while len(self.traps) < self.trap_count:
            cell = self._random_empty_cell(occupied)
            self.traps.add(cell)
            occupied.add(cell)
        self.actions_left = self.max_actions

    def _random_empty_cell(self, occupied: Set[Coordinate]) -> Coordinate:
        while True:
            cell = (random.randrange(self.size), random.randrange(self.size))
            if cell not in occupied:
                return cell

    def display(self) -> str:
        lines: List[str] = []
        for row in range(self.size):
            cells: List[str] = []
            for col in range(self.size):
                coord = (row, col)
                if coord == self.player:
                    cells.append("P")
                elif coord == self.treasure:
                    cells.append("X")
                elif coord in self.traps:
                    cells.append("T")
                else:
                    cells.append(".")
            lines.append(" ".join(cells))
        return "\n".join(lines)

    def move(self, direction: str) -> str:
        deltas = {"w": (-1, 0), "s": (1, 0), "a": (0, -1), "d": (0, 1)}
        direction = direction.lower()

        if direction not in deltas:
            return "请输入 W/A/S/D 来移动，或 Ctrl+C 退出。"

        new_row = self.player[0] + deltas[direction][0]
        new_col = self.player[1] + deltas[direction][1]

        if not (0 <= new_row < self.size and 0 <= new_col < self.size):
            return "撞墙了，换个方向试试！"

        self.player = (new_row, new_col)
        self.actions_left -= 1

        if self.player == self.treasure:
            return "恭喜你找到了宝藏！"

        if self.player in self.traps:
            self.actions_left = max(0, self.actions_left - self.trap_penalty)
            return "哎呀，踩到陷阱！额外扣除 2 次行动。"

        return "继续前进！"

    def is_over(self) -> bool:
        return self.actions_left <= 0 or self.player == self.treasure


def main() -> None:
    print("=== Mini Grid Adventure ===")
    print("找到 X 就赢啦，避开 T，行动次数耗尽会失败！")

    game = GridAdventureGame()

    while not game.is_over():
        print("\n当前网格：")
        print(game.display())
        print(f"剩余行动：{game.actions_left}")

        try:
            command = input("输入方向 (W/A/S/D)：").strip()
        except (EOFError, KeyboardInterrupt):
            print("\n游戏退出，再见！")
            return

        feedback = game.move(command)
        print(feedback)

    if game.player == game.treasure:
        print("\n你成功拿到宝藏，冒险结束！")
    else:
        print("\n行动耗尽，宝藏与你擦肩而过。")


if __name__ == "__main__":
    main()
