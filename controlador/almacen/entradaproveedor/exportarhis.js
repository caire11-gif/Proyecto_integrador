document.getElementById('exportarexcel').addEventListener('click', function(btnElement){
    const originalText = btnElement.innerHTML;
    btnElement.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Procesando...';
    btnElement.disabled = true;

    fetch(`php/entradaproveedor/exportarhist.php`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor');
            }
            return response.json();
        })
        .then(historialCompleto => {
            const workbook = XLSX.utils.book_new();
            
            const data = [];

            const headers = ['Código Compra', 'Fecha', 'Proveedor', 'Total Productos', 'Total Compra', 'Método Pago', 'Registrado Por'];

            data.push(headers);

            historialCompleto.forEach(hist => {
                const rowData = [
                    hist.cod_compra,
                    hist.fecha,
                    hist.proveedor_nombre,
                    hist.total_productos,
                    hist.total_compra,
                    hist.metodo_pago,
                    hist.usuario_registro
                ];
                data.push(rowData);
            });

            const worksheet = XLSX.utils.aoa_to_sheet(data);

            worksheet['!cols'] = [
                { wch: 15 },
                { wch: 25 },
                { wch: 15 },
                { wch: 15 },
                { wch: 40 }
            ];

            XLSX.utils.book_append_sheet(workbook, worksheet, 'Proveedores');

            XLSX.writeFile(workbook, `Historial_Completo.xlsx`);

            btnElement.innerHTML = originalText;
            btnElement.disabled = false;
            Swal.fire({
                icon: "success",
                title: "Descarga Completada",
                width: "350px",
            });
        })
        .catch(error => {
            console.error('Error al exportar Excel:', error);
            console.log('Error al exportar el archivo Excel. Por favor, intente nuevamente.');
            
            // Restaurar botón en caso de error
            btnElement.innerHTML = originalText;
            btnElement.disabled = false;
        });
});

document.getElementById('exportarpdf').addEventListener('click', function(btnElement){
    const originalText = btnElement.innerHTML;
    btnElement.innerHTML = '<i class="fas fa-spinner fa-spin me-1"></i>Procesando...';
    btnElement.disabled = true;
    
    fetch(`php/entradaproveedor/exportarhist.php`)
        .then(response => {
            if (!response.ok) {
                throw new Error('Error en la respuesta del servidor');
            }
            return response.json();
        })
        .then(historialCompleto => {
            const { jsPDF } = window.jspdf;
            const doc = new jsPDF('l', 'mm', 'a4');
            
            // Título del documento
            doc.setFontSize(16);
            doc.text(`Historial Completos`, 14, 15);
            doc.setFontSize(10);
            doc.text(`Fecha de exportación: ${new Date().toLocaleDateString()}`, 14, 22);
            doc.text(`Total de historial: ${historialCompleto.length}`, 14, 28);
            
            const headers = [
                ['Código Compra', 'Fecha', 'Proveedor', 'Total Productos', 'Total Compra', 'Método Pago', 'Registrado Por']
            ];
            
            const body = historialCompleto.map(hist => [
                hist.cod_compra,
                hist.fecha,
                hist.proveedor_nombre,
                hist.total_productos,
                hist.total_compra,
                hist.metodo_pago,
                hist.usuario_registro
            ]);
            
            // Crear tabla PDF
            doc.autoTable({
                head: headers,
                body: body,
                startY: 35,
                styles: { 
                    fontSize: 6, 
                    cellPadding: 1,
                    lineColor: [0, 0, 0],
                    lineWidth: 0.1
                },
                headStyles: { 
                    fillColor: [52, 58, 64],
                    textColor: [255, 255, 255],
                    fontStyle: 'bold',
                    fontSize: 6
                },
                alternateRowStyles: { 
                    fillColor: [240, 240, 240]
                },
                margin: { top: 35 },
                tableWidth: 'wrap'
            });
            
            // Descargar PDF
            doc.save(`Historial_Completo.pdf`);
            
            // Restaurar botón
            btnElement.innerHTML = originalText;
            btnElement.disabled = false;

            Swal.fire({
                icon: "success",
                title: "Descarga Completada",
                width: "350px",
            });
        })
        .catch(error => {
            console.error('Error al exportar PDF:', error);
            alert('Error al exportar el archivo PDF. Por favor, intente nuevamente.');
            
            // Restaurar botón en caso de error
            btnElement.innerHTML = originalText;
            btnElement.disabled = false;
        });
})