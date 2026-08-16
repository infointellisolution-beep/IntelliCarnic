' ==================================================
' IntelliCarnic - Creador Automático del Acceso Directo
' Ejecuta este script UNA SOLA VEZ al instalar el sistema
' en la PC del cliente.
' Crea el icono "IntelliCarnic - TPV" en el Escritorio.
' ==================================================

Dim fso, shell, desktop, shortcut
Dim scriptDir, vbsPath

Set fso   = CreateObject("Scripting.FileSystemObject")
Set shell = CreateObject("WScript.Shell")

' Ruta del escritorio del usuario actual
desktop = shell.SpecialFolders("Desktop")

' Ruta de la carpeta de instalación (donde vive este script)
scriptDir = fso.GetParentFolderName(WScript.ScriptFullName)

' Ruta del lanzador VBS
vbsPath = scriptDir & "\IntelliCarnic.vbs"

' Crear el acceso directo
Set shortcut = shell.CreateShortcut(desktop & "\IntelliCarnic - TPV.lnk")

shortcut.TargetPath       = "wscript.exe"
shortcut.Arguments        = Chr(34) & vbsPath & Chr(34)
shortcut.WorkingDirectory = scriptDir
shortcut.Description      = "Abrir IntelliCarnic - Sistema de Punto de Venta"
shortcut.WindowStyle      = 7  ' Minimized (sin ventanas molestas)

' Icono: usar el .ico si existe en la carpeta, sino usar un icono de Windows
Dim iconPath
iconPath = scriptDir & "\intellicarnic.ico"
If fso.FileExists(iconPath) Then
    shortcut.IconLocation = iconPath & ", 0"
Else
    ' Ícono de "Internet Explorer / Navegador" como fallback
    shortcut.IconLocation = "%SystemRoot%\System32\shell32.dll, 13"
End If

shortcut.Save

MsgBox "¡Acceso directo creado exitosamente!" & vbCrLf & vbCrLf & _
       "Busca el ícono 'IntelliCarnic - TPV' en tu Escritorio." & vbCrLf & _
       "¡Haz doble clic para iniciar el sistema!", _
       vbInformation, "IntelliCarnic - Instalación Completa"

Set shortcut = Nothing
Set fso      = Nothing
Set shell    = Nothing
