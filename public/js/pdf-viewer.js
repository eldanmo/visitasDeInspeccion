import * as pdfjsLib from '../pdfjs/build/pdf.mjs';
import '../pdfjs/web/viewer.mjs';
// import '../pdfjs/build/pdf.worker.mjs';


pdfjsLib.GlobalWorkerOptions.workerSrc = '../pdfjs/build/pdf.worker.mjs';

const url = 'http://visitasdeinspeccion.com/pdf'; // URL completa para el PDF

const loadingTask = pdfjsLib.getDocument(url);
loadingTask.promise.then(function(pdf) {
    const container = document.getElementById('pdfViewer');
    const pdfViewer = new pdfjsViewer.PDFViewer({
        container: container,
    });
    pdfViewer.setDocument(pdf);
}, function (reason) {
    console.error(reason);
});


        //var url = '../docs/prueba_firma.pdf'; // Reemplaza con la ruta de tu archivo PDF

// Asignar la URL del PDF
/*var loadingTask = pdfjsLib.getDocument(url);
loadingTask.promise.then(function(pdf) {
    console.log('PDF loaded');

    // Obtener la primera página del PDF
    pdf.getPage(1).then(function(page) {
        console.log('Page loaded');

        var scale = 1.5;
        var viewport = page.getViewport({ scale: scale });

        // Preparar el canvas usando PDF.js
        var canvas = document.getElementById('pdf-canvas');
        var context = canvas.getContext('2d');
        canvas.height = viewport.height;
        canvas.width = viewport.width;

        // Renderizar la página en el canvas
        var renderContext = {
            canvasContext: context,
            viewport: viewport
        };
        var renderTask = page.render(renderContext);
        renderTask.promise.then(function() {
            console.log('Page rendered');
        });
    });
}, function(reason) {
    console.error(reason);
});


var pdfDoc = null,
        pageNum = 1,
        pageRendering = false,
        pageNumPending = null,
        scale = 1.5,
        canvas = document.getElementById('pdf-canvas'),
        ctx = canvas.getContext('2d');

        function renderPage(num) {
            pageRendering = true;
            pdfDoc.getPage(num).then(function(page) {
                var viewport = page.getViewport({ scale: scale });
                canvas.height = viewport.height;
                canvas.width = viewport.width;
    
                var renderContext = {
                    canvasContext: ctx,
                    viewport: viewport
                };
                var renderTask = page.render(renderContext);
    
                renderTask.promise.then(function() {
                    pageRendering = false;
    
                    if (pageNumPending !== null) {
                        renderPage(pageNumPending);
                        pageNumPending = null;
                    }
                });
            });
    
            document.getElementById('page-num').textContent = num;
        }
    
        function queueRenderPage(num) {
            if (pageRendering) {
                pageNumPending = num;
            } else {
                renderPage(num);
            }
        }
    
        function onPrevPage() {
            if (pageNum <= 1) {
                return;
            }
            pageNum--;
            queueRenderPage(pageNum);
        }
        document.getElementById('prev-page').addEventListener('click', onPrevPage);
    
        function onNextPage() {
            if (pageNum >= pdfDoc.numPages) {
                return;
            }
            pageNum++;
            queueRenderPage(pageNum);
        }
        document.getElementById('next-page').addEventListener('click', onNextPage);
    
        pdfjsLib.getDocument(url).promise.then(function(pdfDoc_) {
            pdfDoc = pdfDoc_;
            document.getElementById('page-count').textContent = pdfDoc.numPages;
            //renderPage(pageNum);
        });
    
        function handleImageUpload(event) {
            var file = event.target.files[0];
            if (file && file.type.startsWith('image/')) {
                var reader = new FileReader();
                reader.onload = function(e) {
                    var img = new Image();
                    img.onload = function() {
                        var imgElement = document.createElement('div');
                        imgElement.style.position = 'absolute';
                        imgElement.style.left = '50px';
                        imgElement.style.top = '50px';
                        imgElement.style.width = img.width + 'px';
                        imgElement.style.height = img.height + 'px';
                        imgElement.style.backgroundImage = 'url(' + img.src + ')';
                        imgElement.style.backgroundSize = '100% 100%';
                        imgElement.style.border = '1px solid black';
                        imgElement.classList.add('resizable');
    
                        var resizer = document.createElement('div');
                        resizer.classList.add('resizer');
                        imgElement.appendChild(resizer);
    
                        document.body.appendChild(imgElement);
    
                        makeElementDraggableAndResizable(imgElement);
                    };
                    img.src = e.target.result;
                };
                reader.readAsDataURL(file);
            }
        }
    
        function makeElementDraggableAndResizable(element) {
            element.addEventListener('mousedown', function(event) {
                if (event.target.classList.contains('resizer')) return;
    
                var shiftX = event.clientX - element.getBoundingClientRect().left;
                var shiftY = event.clientY - element.getBoundingClientRect().top;
    
                function moveAt(pageX, pageY) {
                    element.style.left = pageX - shiftX + 'px';
                    element.style.top = pageY - shiftY + 'px';
                }
    
                function onMouseMove(event) {
                    moveAt(event.pageX, event.pageY);
                }
    
                document.addEventListener('mousemove', onMouseMove);
    
                element.onmouseup = function() {
                    document.removeEventListener('mousemove', onMouseMove);
                    element.onmouseup = null;
                };
            });
    
            element.ondragstart = function() {
                return false;
            };
    
            var resizer = element.querySelector('.resizer');
            resizer.addEventListener('mousedown', function(event) {
                event.stopPropagation();
    
                var initialWidth = element.getBoundingClientRect().width;
                var initialHeight = element.getBoundingClientRect().height;
                var initialX = event.clientX;
                var initialY = event.clientY;
    
                function resizeAt(pageX, pageY) {
                    var newWidth = initialWidth + (pageX - initialX);
                    var newHeight = initialHeight + (pageY - initialY);
                    element.style.width = newWidth + 'px';
                    element.style.height = newHeight + 'px';
                    element.style.backgroundSize = '100% 100%';
                }
    
                function onMouseMove(event) {
                    resizeAt(event.pageX, event.pageY);
                }
    
                document.addEventListener('mousemove', onMouseMove);
    
                document.onmouseup = function() {
                    document.removeEventListener('mousemove', onMouseMove);
                    document.onmouseup = null;
                };
            });
        }
    
        document.getElementById('image-upload').addEventListener('change', handleImageUpload);
    
        document.getElementById('add-image').addEventListener('click', function() {
            document.getElementById('image-upload').click();
        });*/