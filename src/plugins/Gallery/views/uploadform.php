<!DOCTYPE html>
<html>
<head>
    <title>Photo Gallery Upload</title>
    <style>
        #drop-area {
            border: 2px dashed #ccc;
            padding: 20px;
            text-align: center;
        }
    </style>
</head>
<body>
    <h1>Photo Gallery Upload</h1>
    <div id="drop-area">
        <div>Drag & Drop Images Here</div>
        <input type="file" id="fileInput" multiple>
    </div>

    <div id="progress-container">
        <div id="progress-bar"></div>
    </div>
    <div id="message"></div>

    <form id="upload-form" action="?plugin=galleries&page=upload" method="post" enctype="multipart/form-data">
        <input type="file" name="files[]" multiple>
        <input type="submit" value="Upload Manually">
    </form>

    <script src="upload.js"></script>
</body>
<script>
        document.addEventListener('DOMContentLoaded', function () {
            const dropArea = document.getElementById('drop-area');
            const fileInput = document.getElementById('fileInput');
            const progressBar = document.getElementById('progress-bar');
            const message = document.getElementById('message');

            dropArea.addEventListener('dragover', function (e) {
                e.preventDefault();
                dropArea.classList.add('active');
            });

            dropArea.addEventListener('dragleave', function () {
                dropArea.classList.remove('active');
            });

            dropArea.addEventListener('drop', function (e) {
                e.preventDefault();
                dropArea.classList.remove('active');
                const files = e.dataTransfer.files;
                fileInput.files = files;
            });

            fileInput.addEventListener('change', function () {
                dropArea.classList.remove('active');
            });

            const form = document.createElement('form');
            document.body.appendChild(form);
            form.appendChild(fileInput);

            form.addEventListener('submit', function (e) {
                e.preventDefault();

                const formData = new FormData(form);

                const xhr = new XMLHttpRequest();

                xhr.upload.onprogress = function (e) {
                    if (e.lengthComputable) {
                        const percentComplete = (e.loaded / e.total) * 100;
                        progressBar.style.width = percentComplete + '%';
                    }
                };

                xhr.onload = function () {
                    if (xhr.status === 200) {
                        message.innerHTML = 'Upload successful!';
                    } else {
                        message.innerHTML = 'Upload failed.';
                    }
                };

                xhr.open('POST', 'upload.php', true);
                xhr.send(formData);
            });
        });
    </script>
</html>