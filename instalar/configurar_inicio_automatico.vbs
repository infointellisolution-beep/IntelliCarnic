' ==================================================
' Configurar Laragon para Inicio Automatico con Windows
' Ejecuta este script UNA SOLA VEZ en la PC del cliente.
' ==================================================

Dim shell, fso, scriptDir, projectDir, envPath, appName
Set shell = CreateObject("WScript.Shell")
Set fso   = CreateObject("Scripting.FileSystemObject")

scriptDir  = fso.GetParentFolderName(WScript.ScriptFullName)
projectDir = fso.GetParentFolderName(scriptDir)
envPath    = projectDir & "\.env"

' Leer APP_NAME de .env
appName = ""
If fso.FileExists(envPath) Then
    Dim envFile, line, eqPos, key, val
    Set envFile = fso.OpenTextFile(envPath, 1)
    Do Until envFile.AtEndOfStream
        line = Trim(envFile.ReadLine)
        If Left(line, 1) <> "#" And InStr(line, "=") > 0 Then
            eqPos = InStr(line, "=")
            key = Trim(Left(line, eqPos - 1))
            val = Trim(Mid(line, eqPos + 1))
            If (Left(val, 1) = """" And Right(val, 1) = """") Or (Left(val, 1) = "'" And Right(val, 1) = "'") Then
                val = Mid(val, 2, Len(val) - 2)
            End If
            If UCase(key) = "APP_NAME" And val <> "" And LCase(val) <> "laravel" Then
                appName = val
            End If
        End If
    Loop
    envFile.Close
    Set envFile = Nothing
End If

If appName = "" Then
    appName = fso.GetFolder(projectDir).Name
End If

' Ruta del ejecutable de Laragon
Dim laragonPath
laragonPath = "C:\laragon\laragon.exe"

If Not fso.FileExists(laragonPath) Then
    MsgBox "ERROR: No se encontro Laragon en:" & vbCrLf & laragonPath & vbCrLf & vbCrLf & _
           "Verifica que Laragon este instalado en C:\laragon\ e intenta de nuevo.", _
           vbCritical, appName & " - Error de Configuracion"
    WScript.Quit
End If

' Agregar Laragon al Registro de Inicio de Windows (HKCU = solo el usuario actual)
Dim regKey
regKey = "HKCU\Software\Microsoft\Windows\CurrentVersion\Run\Laragon"

shell.RegWrite regKey, Chr(34) & laragonPath & Chr(34) & " --start-all", "REG_SZ"

MsgBox "Configuracion completada!" & vbCrLf & vbCrLf & _
       "Laragon iniciara automaticamente con Windows." & vbCrLf & _
       "Los servicios (Apache / Nginx + MySQL) se encenderan solos." & vbCrLf & vbCrLf & _
       "La proxima vez que enciendas la computadora," & vbCrLf & _
       appName & " estara listo en segundos.", _
       vbInformation, appName & " - Inicio Automatico Activado"

Set shell = Nothing
Set fso   = Nothing
