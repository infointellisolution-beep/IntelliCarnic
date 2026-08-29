' ==================================================
' Creador Automatico Universal de Acceso Directo
' Lee automaticamente el nombre del sistema (APP_NAME)
' y el icono desde el archivo .env y la carpeta del proyecto.
' ==================================================

Dim fso, shell, desktop, shortcut
Dim scriptDir, projectDir, envPath, vbsPath
Dim appName, appUrl, iconPath

Set fso   = CreateObject("Scripting.FileSystemObject")
Set shell = CreateObject("WScript.Shell")

' Rutas del sistema
desktop    = shell.SpecialFolders("Desktop")
scriptDir  = fso.GetParentFolderName(WScript.ScriptFullName)
projectDir = fso.GetParentFolderName(scriptDir)
envPath    = projectDir & "\.env"

' 1. Buscar lanzador VBS en la carpeta
vbsPath = scriptDir & "\iniciar_sistema.vbs"
If Not fso.FileExists(vbsPath) Then
    vbsPath = scriptDir & "\IntelliCarnic.vbs"
    If Not fso.FileExists(vbsPath) Then
        Dim folder, file
        Set folder = fso.GetFolder(scriptDir)
        For Each file In folder.Files
            If LCase(fso.GetExtensionName(file.Name)) = "vbs" And _
               InStr(1, file.Name, "crear_acceso_directo", vbTextCompare) = 0 And _
               InStr(1, file.Name, "configurar_inicio", vbTextCompare) = 0 Then
                vbsPath = file.Path
                Exit For
            End If
        Next
    End If
End If

' 2. Leer APP_NAME de .env
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

' Fallback al nombre de la carpeta del proyecto si APP_NAME es Laravel o vacio
If appName = "" Then
    appName = fso.GetFolder(projectDir).Name
End If

' 3. Buscar archivo de icono (.ico) automaticamente
iconPath = ""
Dim installFolder, f
Set installFolder = fso.GetFolder(scriptDir)
For Each f In installFolder.Files
    If LCase(fso.GetExtensionName(f.Name)) = "ico" Then
        iconPath = f.Path
        Exit For
    End If
Next

If iconPath = "" Then
    If fso.FileExists(projectDir & "\public\favicon.ico") Then
        iconPath = projectDir & "\public\favicon.ico"
    End If
End If

' 4. Crear el acceso directo en el Escritorio
Dim lnkPath
lnkPath = desktop & "\" & appName & " - TPV.lnk"
Set shortcut = shell.CreateShortcut(lnkPath)

shortcut.TargetPath       = "wscript.exe"
shortcut.Arguments        = Chr(34) & vbsPath & Chr(34)
shortcut.WorkingDirectory = scriptDir
shortcut.Description      = "Abrir " & appName & " - Sistema de Punto de Venta"
shortcut.WindowStyle      = 7  ' Minimized

If iconPath <> "" Then
    shortcut.IconLocation = iconPath & ", 0"
Else
    shortcut.IconLocation = "%SystemRoot%\System32\shell32.dll, 13"
End If

shortcut.Save

MsgBox "Acceso directo creado exitosamente!" & vbCrLf & vbCrLf & _
       "Icono: " & appName & " - TPV" & vbCrLf & _
       "Ubicacion: En tu Escritorio." & vbCrLf & vbCrLf & _
       "Haz doble clic para abrir el sistema!", _
       vbInformation, appName & " - Instalacion Completada"

Set shortcut = Nothing
Set fso      = Nothing
Set shell    = Nothing
