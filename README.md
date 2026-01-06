### Composer 
```
composer install
```

### Setup docker image.
```
docker build -t server-app .
```

#### Start Server
```
docker run -d -p 8000:80 --name server-app server-app
```

#### Initial Curl
Returns 401
```
curl -i -v  http://localhost:8000/src/sync.php
``` 

#### Login 
```
curl -X POST http://localhost:8000/src/sync.php \
     -d "action=login" \
     -d "username=username-here" \
     -d "password=password-here"
```     

#### List Files
```
curl -X GET http://localhost:8000/src/sync.php \
     -H "X-API-KEY: your-secret-key-here'" \
     -H "X-AUTH-TOKEN: your-secret-token-here"
```

#### Download a File
```
curl -X GET "http://localhost:8000/src/sync.php?download=filename.tar" \
     -H "X-API-KEY: your-secret-key-here" \
     -H "X-AUTH-TOKEN: your-secret-token-here" \
     --output filename.tar
```

#### Example for a 1024 byte file
```
curl -I -X POST http://localhost:8000/src/sync.php \
     -H "X-API-KEY: your-secret-key-here" \
     -H "X-AUTH-TOKEN: your-secret-token-here" \
     -H "Tus-Resumable: 1.0.0" \
     -H "Upload-Length: 1024" \
     -H "Upload-Metadata: relativePath dXBsb2Fkcy9teS9maWxlLnR4dA==" 
```

#### Example Check Progress
```
curl -I -X HEAD http://localhost:8000/src/sync.php \
     -H "X-API-KEY: your-secret-key-here" \
     -H "X-AUTH-TOKEN: your-secret-token-here" \
     -H "Tus-Resumable: 1.0.0"
```

#### Send Data
```
curl -X PATCH http://localhost:8000/src/sync.php \
     -H "X-API-KEY: your-secret-key-here" \
     -H "X-AUTH-TOKEN: your-secret-token-here" \
     -H "Tus-Resumable: 1.0.0" \
     -H "Upload-Offset: 0" \
     -H "Content-Type: application/offset+octet-stream" \
     --data-binary "@path/to/local/file.txt"
```