/**
 * Generate product code (YP + next ID)
 */
async function generateProductCode() {
    try {
        const response = await fetch('../api/get_next_product_id.php');
        const data = await response.json();

        if (data.success) {
            const productCode = 'YP' + data.next_id;
            document.getElementById('productCode').value = productCode;
        } else {
            // Fallback: use timestamp
            const productCode = 'YP' + Date.now();
            document.getElementById('productCode').value = productCode;
        }
    } catch (error) {
        console.error('Error generating product code:', error);
        // Fallback: use timestamp
        const productCode = 'YP' + Date.now();
        document.getElementById('productCode').value = productCode;
    }
}
