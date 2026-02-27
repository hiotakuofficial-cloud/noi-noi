# Android App Links Setup for hiotaku.in

## Step 1: Get SHA256 Fingerprint

### For Debug Build:
```bash
keytool -list -v -keystore ~/.android/debug.keystore -alias androiddebugkey -storepass android -keypass android | grep SHA256
```

### For Release Build (Play Store):
```bash
# Find your keystore file (usually .jks or .keystore)
keytool -list -v -keystore /path/to/your-keystore.jks -alias your-alias-name
```

### From Play Console:
1. Go to: Play Console → Your App → Setup → App Integrity
2. Copy SHA-256 certificate fingerprint

## Step 2: Update assetlinks.json

Replace `REPLACE_WITH_YOUR_SHA256_FINGERPRINT` with your actual SHA256 fingerprint (without colons).

Example:
```
14:6D:E9:83:C5:73:06:50:D8:EE:B9:95:2F:34:FC:64:16:A0:83:42:E6:1D:BE:A8:8A:04:96:B2:3F:CF:44:E5
```
Becomes:
```
146DE983C5730650D8EEB9952F34FC6416A08342E61DBEA88A0496B23FCF44E5
```

## Step 3: Upload to Server

Upload `assetlinks.json` to:
```
https://hiotaku.in/.well-known/assetlinks.json
```

Server path:
```
/var/www/hiotaku.in/.well-known/assetlinks.json
```

Set permissions:
```bash
chmod 644 assetlinks.json
```

## Step 4: Verify

Test URL in browser:
```
https://hiotaku.in/.well-known/assetlinks.json
```

Should return JSON file with 200 status.

## Step 5: Test App Links

After uploading, rebuild and install app. Then test:
```
adb shell am start -a android.intent.action.VIEW -d "https://hiotaku.in"
```

App should open automatically!
