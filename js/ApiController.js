export class ApiController {
    /**
     * ピース交換のリクエストをサーバーに送信する。
     * @param {number} r1 ピース1の行
     * @param {number} c1 ピース1の列
     * @param {number} r2 ピース2の行
     * @param {number} c2 ピース2の列
     * @returns {Promise<object>} サーバーからのレスポンスデータ
     */
    async swapPieces(r1, c1, r2, c2) {
        const requestData = {
            action: 'swapPieces',
            r1, c1, r2, c2
        };

        try {
            const response = await fetch('apiManager.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(requestData)
            });

            if (!response.ok) {
                throw new Error(`サーバーエラー: ${response.status}`);
            }

            return await response.json();
        } catch (error) {
            console.error('API通信に失敗しました:', error);
            // NOTE: エラー時もフロントが継続できるよう空データを返す。
            return { status: 'error', chainSteps: [] };
        }
    }
}