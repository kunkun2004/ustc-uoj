package answer;
import java.util.*;
class Solution {
    public int solve(int[][] grid) {
        if (grid == null || grid.length == 0) return 0;
        int row = grid.length, columns = grid[0].length, count = 0;
        for (int i = 0; i < row; i++) {
            for (int j = 0; j < columns; j++) {
                if (grid[i][j] == 1) {
                    bfs(grid, i, j, row, columns);
                    count++;
                }
            }
        }
        return count;
    }

    private void bfs(int[][] grid, int i, int j, int row, int columns) {
        Queue<Integer> queue = new LinkedList<>();
        queue.add(i * columns + j);
        while (!queue.isEmpty()) {
            int id = queue.remove();
            int r = id / columns, c = id % columns;
            if (r - 1 >= 0 && grid[r - 1][c] == 1) {
                queue.add((r - 1) * columns + c);
                grid[r - 1][c] = 0;
            }
            if (r + 1 < row && grid[r + 1][c] == 1) {
                queue.add((r + 1) * columns + c);
                grid[r + 1][c] = 0;
            }
            if (c - 1 >= 0 && grid[r][c - 1] == 1) {
                queue.add(r * columns + c - 1);
                grid[r][c - 1] = 0;
            }
            if (c + 1 < columns && grid[r][c + 1] == 1) {
                queue.add(r * columns + c + 1);
                grid[r][c + 1] = 0;
            }
        }
    }
}

class Main{
	public static void main(String[] args) {
	    
        Scanner scanner = new Scanner(System.in);

        System.out.println("请输入矩阵的行数和列数：");
        int rows = scanner.nextInt();
        int cols = scanner.nextInt();

        int[][] matrix = new int[rows][cols];

        System.out.println("请逐行输入矩阵元素，每个元素以空格分隔：");
        for (int i = 0; i < rows; i++) {
            for (int j = 0; j < cols; j++) {
                matrix[i][j] = scanner.nextInt();
            }
        }

        System.out.println("您输入的矩阵是：");
        for (int i = 0; i < rows; i++) {
            for (int j = 0; j < cols; j++) {
                System.out.print(matrix[i][j] + " ");
            }
            System.out.println();
        }

        scanner.close();
		Solution solution = new Solution ();
		int result = solution.solve(matrix);
		System.out.println("该矩阵王国的数量是：" + result);
		
	}
}