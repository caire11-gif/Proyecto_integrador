//============================================================================================
//                                        USUARIOS
//============================================================================================
    document.addEventListener('DOMContentLoaded', async function() {
        // Realizar la llamada AJAX al archivo PHP
        fetch('../../modelo/vendedor/usuarioinfo.php')
            .then(function(response){
                const contentType = response.headers.get('content-type');
                
                if (!contentType || !contentType.includes('application/json')) {
                    return response.text().then(text => {
                        throw new TypeError("Response wasn't JSON: " + text);
                    });
                }

                return response.json();
            })
            .then(function(data1){
                document.getElementById('usuarioAvatar').innerHTML = data1.iniciales;
                document.getElementById('userName').innerHTML=data1.nombre_apellido;
            })
            .catch(function(error){
                console.error('Error al cargar el contenido PHP: ',error);
            })
    });

//============================================================================================
//                                      DROPDOWN
//============================================================================================
    const dropdownBtn = document.getElementById("dropdownBtn");
    const dropdownList = document.getElementById("dropdownList");
    const arrow = document.getElementById("arrow");

    dropdownBtn.addEventListener("click", () => {
        const isVisible = dropdownList.style.display === "block";
        dropdownList.style.display = isVisible ? "none" : "block";
        arrow.style.transform = isVisible ? "rotate(0deg)" : "rotate(180deg)";
    });
                            
    document.addEventListener("click", (e) => {
        if (!dropdownBtn.contains(e.target) && !dropdownList.contains(e.target)) {
            dropdownList.style.display = "none";
            arrow.style.transform = "rotate(0deg)";
        }
    });

//============================================================================================
//                                      BARRA LATERAL
//============================================================================================
    const mobileMenuBtn = document.getElementById('mobileMenuBtn');
    const barraLateral = document.getElementById('barra-lateral');
        
    if(mobileMenuBtn && barraLateral) {
        mobileMenuBtn.addEventListener('click', () => {
            barraLateral.classList.toggle('mobile-open');
        });
    }

//============================================================================================
//                                         IMPRIMIR
//============================================================================================
function imprimirDocumento() {
    const modalContent = document.getElementById('modalContent');
    const printContent = document.getElementById('documentoPrint');
    
    // Copiar el contenido actual al área de impresión
    printContent.innerHTML = modalContent.innerHTML;
    
    // Aplicar estilos específicos para impresión
    const docElement = printContent.querySelector('.documento-empresa');
    if (docElement) {
        docElement.classList.add('documento-empresa-pdf');
        // QUITAR transformación para impresión
        docElement.style.transform = 'none';
        docElement.style.maxWidth = '700px'; // Tamaño fijo para impresión
        docElement.style.margin = '0 auto'; // Centrar
    }
    
    // Mostrar área de impresión
    printContent.style.display = 'block';
    
    // Imprimir
    window.print();
    
    // Restaurar
    setTimeout(() => {
        printContent.style.display = 'none';
        if (docElement) {
            docElement.classList.remove('documento-empresa-pdf');
            docElement.style.transform = 'scale(0.9)';
            docElement.style.maxWidth = '100%';
            docElement.style.margin = '0 auto';
        }
    }, 500);
}

//============================================================================================
//                                        FILTROS
//============================================================================================
    document.getElementById('btnLimpiarFiltros').addEventListener('click', function() {
        document.getElementById('filtroTipo').selectedIndex = 0;
        aplicarFiltros();
    });

    function aplicarFiltros() {
        const tipoValue = document.getElementById('filtroTipo').value;
        
        const tipoText = tipoValue ? 
            document.getElementById('filtroCategoria').options[document.getElementById('filtroCategoria').selectedIndex].textContent : '';
            
        const rows = document.querySelectorAll('tbody tr');
        let visibleCount = 0;
            
        rows.forEach(row => {
            if (row.cells.length < 8) return;

            const tipoDocumento = row.cells[2].textContent; // Cambié de 6 a 7 porque agregué columna

            const matchTipo = !tipoValue || tipoDocumento === tipoText;
                
            if (matchTipo) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });
    }

//============================================================================================
//                                      DESCARGAR PDF
//============================================================================================
function descargarPDF() {
    // Mostrar documento de impresión temporalmente
    const documentoPrint = document.getElementById('documentoPrint');
    const modalContent = document.getElementById('modalContent');
    
    // Guardar estado original
    const originalDisplay = documentoPrint.style.display;
    const originalModalDisplay = modalContent.style.display;
    
    documentoPrint.style.display = 'block';
    modalContent.style.display = 'none';
    
    // Forzar dimensiones fijas para mejor captura
    documentoPrint.style.width = '700px';
    documentoPrint.style.height = 'auto';
    documentoPrint.style.position = 'fixed';
    documentoPrint.style.left = '0';
    documentoPrint.style.top = '0';
    documentoPrint.style.zIndex = '9999';
    documentoPrint.style.background = 'white';
    
    // Agregar clase para PDF
    const docElement = documentoPrint.querySelector('.documento-empresa');
    docElement.classList.add('documento-empresa-pdf');
    
    // Aumentar el tamaño para mejor calidad
    docElement.style.transform = 'scale(1)';
    docElement.style.transformOrigin = 'top left';
    
    console.log('Generando PDF...');
    
    // Configuración optimizada
    const options = {
        scale: 3, // Mayor resolución
        useCORS: true,
        backgroundColor: '#ffffff',
        logging: true,
        removeContainer: true,
        width: 700,
        height: docElement.scrollHeight,
        windowWidth: 700,
        windowHeight: docElement.scrollHeight,
        onclone: function(clonedDoc) {
            // Asegurar que los estilos se apliquen en el clon
            const clonedElement = clonedDoc.querySelector('.documento-empresa');
            if (clonedElement) {
                clonedElement.style.width = '700px';
                clonedElement.style.background = 'white';
            }
        }
    };

    // Esperar un poco más para renderizado completo
    setTimeout(() => {
        html2canvas(documentoPrint, options).then(canvas => {
            console.log('Canvas generado:', canvas.width, 'x', canvas.height);
            
            const pdf = new jspdf.jsPDF({
                orientation: 'portrait',
                unit: 'mm',
                format: 'a4'
            });
            
            const pageWidth = pdf.internal.pageSize.getWidth();
            const pageHeight = pdf.internal.pageSize.getHeight();
            
            const imgWidth = pageWidth - 20; // Margen de 10mm cada lado
            const imgHeight = (canvas.height * imgWidth) / canvas.width;
            
            console.log('Dimensiones PDF:', imgWidth, 'x', imgHeight);
            
            let heightLeft = imgHeight;
            let position = 10;
            let pageNumber = 1;
            
            // Primera página
            pdf.addImage(canvas, 'PNG', 10, position, imgWidth, imgHeight);
            
            // Páginas adicionales si el contenido es muy largo
            while (heightLeft > pageHeight) {
                position = position - pageHeight + 10;
                pdf.addPage();
                pdf.addImage(canvas, 'PNG', 10, position, imgWidth, imgHeight);
                heightLeft -= pageHeight;
                pageNumber++;
            }
            
            // Nombre del archivo
            const tipoDoc = 'Factura/Boleta';
            const numero = '0000001';
            
            pdf.save(`${tipoDoc}_${numero}.pdf`);
            
            console.log('PDF generado exitosamente');
            
        }).catch(error => {
            console.error('Error detallado:', error);
            alert('Error al generar el PDF: ' + error.message);
        }).finally(() => {
            // Restaurar siempre
            restoreOriginalState();
        });
    }, 1500);
    
    
    // Función para restaurar estado original
    function restoreOriginalState() {
        documentoPrint.style.display = originalDisplay;
        modalContent.style.display = originalModalDisplay;
        documentoPrint.style.width = '';
        documentoPrint.style.height = '';
        documentoPrint.style.position = '';
        documentoPrint.style.left = '';
        documentoPrint.style.top = '';
        documentoPrint.style.zIndex = '';
        documentoPrint.style.background = '';
        docElement.classList.remove('documento-empresa-pdf');
        docElement.style.transform = '';
        docElement.style.transformOrigin = '';
    }
}