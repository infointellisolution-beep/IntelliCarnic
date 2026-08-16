' ==================================================
' IntelliCarnic - Configurar Laragon para Inicio Automático con Windows
' Ejecuta este script UNA SOLA VEZ en la PC del cliente
' para activar la Opción 2 (inicio nativo al encender la PC).
' ==================================================

Dim shell, WshEnv
Set shell = CreateObject("WScript.Shell")

' Ruta del ejecutable de Laragon
Dim laragonPath
laragonPath = "C:\laragon\laragon.exe"

Dim fso
Set fso = CreateObject("Scripting.FileSystemObject")

If Not fso.FileExists(laragonPath) Then
    MsgBox "ERROR: No se encontró Laragon en:" & vbCrLf & laragonPath & vbCrLf & vbCrLf & _
           "Verifica que Laragon esté instalado en C:\laragon\ e intenta de nuevo.", _
           vbCritical, "IntelliCarnic - Error de Configuración"
    WScript.Quit
End If

' Agregar Laragon al Registro de Inicio de Windows (HKCU = solo el usuario actual)
Dim regKey
regKey = "HKCU\Software\Microsoft\Windows\CurrentVersion\Run\Laragon"

shell.RegWrite regKey, Chr(34) & laragonPath & Chr(34) & " --start-all", "REG_SZ"

MsgBox "¡Configuración completada!" & vbCrLf & vbCrLf & _
       "✅ Laragon iniciará automáticamente con Windows." & vbCrLf & _
       "✅ Los servicios (Apache + MySQL) se encenderán solos." & vbCrLf & vbCrLf & _
       "La próxima vez que enciendas la computadora," & vbCrLf & _
       "IntelliCarnic estará listo en segundos.", _
       vbInformation, "IntelliCarnic - Inicio Automático Activado"

Set shell = Nothing
Set fso   = Nothing
