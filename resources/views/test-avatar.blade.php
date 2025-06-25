<!DOCTYPE html>
<html>
<head>
    <title>Test Avatar Upload</title>
    <meta name="csrf-token" content="{{ csrf_token() }}">
</head>
<body>
    <h1>Test Avatar Upload</h1>

    <form id="avatarForm" enctype="multipart/form-data">
        <input type="file" name="avatar" accept="image/*" required>
        <button type="submit">Subir Avatar</button>
    </form>

    <div id="result"></div>

    <script>
        document.getElementById('avatarForm').addEventListener('submit', function(e) {
            e.preventDefault();

            const formData = new FormData(this);
            const resultDiv = document.getElementById('result');

            resultDiv.innerHTML = 'Subiendo...';

            fetch('/test-avatar-upload', {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content')
                },
                body: formData
            })
            .then(response => response.json())
            .then(data => {
                resultDiv.innerHTML = '<pre>' + JSON.stringify(data, null, 2) + '</pre>';
            })
            .catch(error => {
                resultDiv.innerHTML = 'Error: ' + error.message;
            });
        });
    </script>
</body>
</html>
