import html2pdf from 'html2pdf.js';

export function convertHtmlPdf(divElement){
    const element =document.getElementById(divElement);

    html2pdf().from(element).save();
}