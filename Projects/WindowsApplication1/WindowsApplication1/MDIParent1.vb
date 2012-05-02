Imports System.Windows.Forms
Imports Word = Microsoft.Office.Interop.Word
Imports Microsoft.Office.Interop
Imports System.Runtime.InteropServices

Public Class MDIParent1

    Private Sub ShowNewForm(ByVal sender As Object, ByVal e As EventArgs)
        ' Cree una nueva instancia del formulario secundario.
        Dim ChildForm As New Form1
        ' Conviértalo en un elemento secundario de este formulario MDI antes de mostrarlo.
        ChildForm.MdiParent = Me

        m_ChildFormNumber += 1
        ChildForm.Text = "Ventana " & m_ChildFormNumber

        ChildForm.Show()
    End Sub

    Public Function convertirdoc()
        Dim i As Integer
        i = 0

        Dim activeChild As Form2 = Me.ActiveMdiChild
        If (Not activeChild Is Nothing) Then
            Dim lines() As String = activeChild.RichTextBox1.Lines
            Dim datos(0 To activeChild.RichTextBox1.Lines.GetLength(0), 0 To 2) As String
            Dim doit As String

            doit = "false"
            For Each line As String In activeChild.RichTextBox1.Lines
                Dim primercaracter As String

                If (activeChild.RichTextBox1.Lines(i).ToString = "") Then
                    'MsgBox(RichTextBox1.Lines(i).ToString & vbCrLf & i)
                Else
                    primercaracter = activeChild.RichTextBox1.Lines(i).Substring(0, 1).ToString
                    If (primercaracter = "<") Then
                        datos(i, 0) = "newtake"
                        datos(i, 1) = activeChild.RichTextBox1.Lines(i).Trim.ToString
                        datos(i, 2) = "-NADA-"
                        doit = "true"

                    ElseIf (primercaracter = "*") Then

                        Dim textof As String
                        Dim textofs() As String
                        textof = activeChild.RichTextBox1.Lines(i).ToString
                        textofs = textof.Split("*")
                        datos(i, 0) = "voz"
                        datos(i, 1) = textofs(1).Trim.ToString
                        datos(i, 2) = textofs(2).Trim.ToString
                    Else
                        If (doit = "false") Then

                            datos(i, 0) = "titulo"
                            datos(i, 1) = activeChild.RichTextBox1.Lines(i).ToString
                            datos(i, 2) = "-NADA-"
                        Else
                            If (datos(i - 1, 2) = "") Then
                                'MsgBox("fila : " & i & vbCrLf & "previo : " & datos(i - 2, 2) & vbCrLf & "actual : " & RichTextBox1.Lines(i).ToString)
                                datos(i - 2, 2) = datos(i - 2, 2) & " " & activeChild.RichTextBox1.Lines(i).ToString
                            Else
                                'MsgBox("fila : " & i & vbCrLf & "previo : " & datos(i - 1, 2) & vbCrLf & "actual : " & RichTextBox1.Lines(i).ToString)
                                datos(i - 1, 2) = datos(i - 1, 2) & " " & activeChild.RichTextBox1.Lines(i).ToString
                            End If

                        End If





                    End If
                End If
                Me.Text = "Falten " & (datos.GetUpperBound(0) - i) - 1 & " per convertir"
                i = i + 1
            Next line

            Dim datos2(0 To activeChild.RichTextBox1.Lines.GetLength(0), 0 To 2) As String
            Dim canti As String = datos.GetUpperBound(0)
            Dim p As Integer = 0

            For j As Integer = 1 To canti - 1 Step +1

                If datos(j - 1, 1) = "" Then

                Else
                    datos2(p, 1) = datos(j - 1, 1)
                    datos2(p, 2) = datos(j - 1, 2)
                    p = p + 1
                End If
            Next
            Dim takes As Boolean = False


            For n As Integer = 1 To datos2.GetUpperBound(0) Step +1
                If (n <= 1) Then
                    RichTextBox1.Text = datos2(n - 1, 1) & "|"
                Else
                    Dim pric As String

                    If (datos2(n - 1, 1) = "") Then
                        ' oTable.Rows(n - 1).Delete()
                    Else

                        If datos2(n - 1, 1) IsNot "" Then
                            pric = datos2(n - 1, 1).Substring(0, 1).ToString
                            If (pric = "<") Then
                                RichTextBox1.Text = RichTextBox1.Text & datos2(n - 1, 1).ToString & "|"
                                takes = True
                            Else
                                If takes = True Then
                                    RichTextBox1.Text = RichTextBox1.Text & "*" & datos2(n - 1, 1).ToString & "*" & "+" & datos2(n - 1, 2).Trim.ToString & "|"
                                Else
                                    RichTextBox1.Text = RichTextBox1.Text & datos2(n - 1, 1).ToString & "|"

                                End If

                            End If
                        Else
                            'oTable.Rows(n - 1).Delete()
                        End If

                    End If
                End If

            Next
            Dim saveFileDialog1 As New SaveFileDialog()
            saveFileDialog1.Filter = "Document de Takes Convertit|*.txt"
            saveFileDialog1.Title = "Guardar Document de Takes Convertit"

            If saveFileDialog1.ShowDialog() = DialogResult.OK Then
                RichTextBox1.SaveFile(saveFileDialog1.FileName, _
                RichTextBoxStreamType.PlainText)
                MsgBox("correcto")

            End If

        End If
        Return True
    End Function
    Private Sub OpenFile(ByVal sender As Object, ByVal e As EventArgs) Handles OpenToolStripMenuItem.Click, OpenToolStripButton.Click
        Dim openFileDialog1 As New OpenFileDialog()
        openFileDialog1.Filter = "Document convertit |*.txt"
        openFileDialog1.Title = "Selecciona el document..."
        If openFileDialog1.ShowDialog() = DialogResult.OK Then
            Dim sr As New System.IO.StreamReader(openFileDialog1.FileName, System.Text.Encoding.Default)
            Dim takes As String = sr.ReadToEnd.ToString
            Dim textof As String
            Dim textofs() As String
            Dim texto2() As String
            textof = takes
            textofs = textof.Split("|")
            Dim primercaracter As String

            ' Cree una nueva instancia del formulario secundario.
            Dim ChildForm As New Form2
            ' Conviértalo en un elemento secundario de este formulario MDI antes de mostrarlo.
            ChildForm.MdiParent = Me

            m_ChildFormNumber += 1
            ChildForm.Text = "Archivo " & m_ChildFormNumber & " - Nombre : " & openFileDialog1.FileName
            ChildForm.Show()

            For n As Integer = 0 To textofs.GetUpperBound(0) - 1 Step +1
                If textofs(n).Length > 1 Then
                    primercaracter = textofs(n).Substring(0, 1).ToString
                    If primercaracter = "<" Then
                        ChildForm.RichTextBox1.Text = ChildForm.RichTextBox1.Text & vbCrLf & vbCrLf & vbCrLf & textofs(n) & vbCrLf

                    ElseIf primercaracter = "*" Then
                        texto2 = textofs(n).Split("+")
                        ChildForm.RichTextBox1.Text = ChildForm.RichTextBox1.Text & vbCrLf & vbCrLf & texto2(0) & "    " & texto2(1) & vbCrLf
                    Else
                        ChildForm.RichTextBox1.Text = ChildForm.RichTextBox1.Text & textofs(n) & vbCrLf

                    End If
                End If
            Next
            sr.Close()

        End If
    End Sub

    Private Sub SaveAsToolStripMenuItem_Click(ByVal sender As Object, ByVal e As EventArgs) Handles SaveAsToolStripMenuItem.Click
        Dim SaveFileDialog As New SaveFileDialog
        SaveFileDialog.InitialDirectory = My.Computer.FileSystem.SpecialDirectories.MyDocuments
        SaveFileDialog.Filter = "Archivos de texto (*.txt)|*.txt|Todos los archivos (*.*)|*.*" 

        If (SaveFileDialog.ShowDialog(Me) = System.Windows.Forms.DialogResult.OK) Then
            Dim FileName As String = SaveFileDialog.FileName
            ' TODO: agregue código aquí para guardar el contenido actual del formulario en un archivo.
        End If
    End Sub


    Private Sub ExitToolsStripMenuItem_Click(ByVal sender As Object, ByVal e As EventArgs) Handles ExitToolStripMenuItem.Click
        Me.Close()
    End Sub

    Private Sub CutToolStripMenuItem_Click(ByVal sender As Object, ByVal e As EventArgs)
        ' Utilice My.Computer.Clipboard para insertar el texto o las imágenes seleccionadas en el Portapapeles
    End Sub

    Private Sub CopyToolStripMenuItem_Click(ByVal sender As Object, ByVal e As EventArgs)
        ' Utilice My.Computer.Clipboard para insertar el texto o las imágenes seleccionadas en el Portapapeles
    End Sub

    Private Sub PasteToolStripMenuItem_Click(ByVal sender As Object, ByVal e As EventArgs)
        'Utilice My.Computer.Clipboard.GetText() o My.Computer.Clipboard.GetData para recuperar la información del Portapapeles.
    End Sub

    Private Sub CascadeToolStripMenuItem_Click(ByVal sender As Object, ByVal e As EventArgs)
        Me.LayoutMdi(MdiLayout.Cascade)
    End Sub

    Private Sub TileVerticalToolStripMenuItem_Click(ByVal sender As Object, ByVal e As EventArgs)
        Me.LayoutMdi(MdiLayout.TileVertical)
    End Sub

    Private Sub TileHorizontalToolStripMenuItem_Click(ByVal sender As Object, ByVal e As EventArgs)
        Me.LayoutMdi(MdiLayout.TileHorizontal)
    End Sub

    Private Sub ArrangeIconsToolStripMenuItem_Click(ByVal sender As Object, ByVal e As EventArgs)
        Me.LayoutMdi(MdiLayout.ArrangeIcons)
    End Sub

    Private Sub CloseAllToolStripMenuItem_Click(ByVal sender As Object, ByVal e As EventArgs)
        ' Cierre todos los formularios secundarios del principal.
        For Each ChildForm As Form In Me.MdiChildren
            ChildForm.Close()
        Next
    End Sub

    Private m_ChildFormNumber As Integer

    Private Sub OptionsToolStripMenuItem_Click(ByVal sender As System.Object, ByVal e As System.EventArgs) Handles OptionsToolStripMenuItem.Click
        ' Displays an OpenFileDialog so the user can select a Cursor.
        Dim openFileDialog1 As New OpenFileDialog()
        openFileDialog1.Filter = "Original |*.doc"
        openFileDialog1.Title = "Selecciona el document..."

        ' Show the Dialog.
        ' If the user clicked OK in the dialog and 
        ' a .CUR file was selected, open it.
        If openFileDialog1.ShowDialog() = DialogResult.OK Then
            Dim doc As New Word.Document
            Dim WordApp As New Word.Application()

            Dim file As Object = openFileDialog1.FileName
            Dim Nothingobj As Object = System.Reflection.Missing.Value
            doc = WordApp.Documents.Open(file, Nothingobj, Nothingobj, Nothingobj, Nothingobj, Nothingobj, Nothingobj, Nothingobj, Nothingobj, Nothingobj, Nothingobj, Nothingobj)
            doc.ActiveWindow.Selection.WholeStory()
            doc.ActiveWindow.Selection.Copy()
            Dim data As IDataObject = Clipboard.GetDataObject()
            RichTextBox1.Text = data.GetData(DataFormats.Text).ToString()
            Dim datos(0 To RichTextBox1.Lines.GetLength(0), 0 To 2) As String
            If RichTextBox1.Text = "" Then
                MsgBox("Carrega l'arxiu primer!!", vbInformation)

            Else
                Dim i As Integer
                i = 0
                Dim lines() As String = RichTextBox1.Lines




                Dim doit As String
                doit = "false"
                For Each line As String In RichTextBox1.Lines
                    Dim primercaracter As String

                    If (RichTextBox1.Lines(i).ToString = "") Then
                        'MsgBox(RichTextBox1.Lines(i).ToString & vbCrLf & i)
                    Else
                        primercaracter = RichTextBox1.Lines(i).Substring(0, 1).ToString
                        If (primercaracter = "<") Then
                            datos(i, 0) = "newtake"
                            datos(i, 1) = RichTextBox1.Lines(i).Trim.ToString
                            datos(i, 2) = "-NADA-"
                            doit = "true"

                        ElseIf (primercaracter = "*") Then

                            Dim textof As String
                            Dim textofs() As String
                            textof = RichTextBox1.Lines(i).ToString
                            textofs = textof.Split("*")
                            datos(i, 0) = "voz"
                            datos(i, 1) = textofs(1).Trim.ToString
                            datos(i, 2) = textofs(2).Trim.ToString
                        Else
                            If (doit = "false") Then

                                datos(i, 0) = "titulo"
                                datos(i, 1) = RichTextBox1.Lines(i).ToString
                                datos(i, 2) = "-NADA-"
                            Else
                                If (datos(i - 1, 2) = "") Then
                                    'MsgBox("fila : " & i & vbCrLf & "previo : " & datos(i - 2, 2) & vbCrLf & "actual : " & RichTextBox1.Lines(i).ToString)
                                    datos(i - 2, 2) = datos(i - 2, 2) & " " & RichTextBox1.Lines(i).ToString
                                Else
                                    'MsgBox("fila : " & i & vbCrLf & "previo : " & datos(i - 1, 2) & vbCrLf & "actual : " & RichTextBox1.Lines(i).ToString)
                                    datos(i - 1, 2) = datos(i - 1, 2) & " " & RichTextBox1.Lines(i).ToString
                                End If

                            End If





                        End If
                    End If
                    Me.Text = "Falten " & datos.GetUpperBound(0) - i & " per convertir"
                    i = i + 1
                Next line

                Dim datos2(0 To RichTextBox1.Lines.GetLength(0), 0 To 2) As String
                Dim canti As String = datos.GetUpperBound(0)
                Dim p As Integer = 0

                For j As Integer = 1 To canti - 1 Step +1

                    If datos(j - 1, 1) = "" Then

                    Else

                        datos2(p, 1) = datos(j - 1, 1)
                        datos2(p, 2) = datos(j - 1, 2)
                        p = p + 1
                    End If
                Next
                Dim takes As Boolean = False


                For n As Integer = 1 To datos2.GetUpperBound(0) Step +1
                    If (n <= 1) Then
                        RichTextBox1.Text = datos2(n - 1, 1) & "|"
                    Else
                        Dim pric As String

                        If (datos2(n - 1, 1) = "") Then
                            ' oTable.Rows(n - 1).Delete()
                        Else

                            If datos2(n - 1, 1) IsNot "" Then
                                pric = datos2(n - 1, 1).Substring(0, 1).ToString
                                If (pric = "<") Then
                                    RichTextBox1.Text = RichTextBox1.Text & datos2(n - 1, 1).ToString & "|"
                                    takes = True
                                Else
                                    If takes = True Then
                                        RichTextBox1.Text = RichTextBox1.Text & "*" & datos2(n - 1, 1).ToString & "*" & "+" & datos2(n - 1, 2).Trim.ToString & "|"
                                    Else
                                        RichTextBox1.Text = RichTextBox1.Text & datos2(n - 1, 1).ToString & "|"

                                    End If

                                End If
                            Else
                                'oTable.Rows(n - 1).Delete()
                            End If

                        End If
                    End If

                Next

                Dim saveFileDialog1 As New SaveFileDialog()
                saveFileDialog1.Filter = "Document de Takes Convertit|*.txt"
                saveFileDialog1.Title = "Guardar Document de Takes Convertit"

                If saveFileDialog1.ShowDialog() = DialogResult.OK Then
                    RichTextBox1.SaveFile(saveFileDialog1.FileName, _
                    RichTextBoxStreamType.PlainText)
                    MsgBox("correcto")


                    doc.Close()
                    WordApp.Application.Quit()
                End If



            End If
        End If
    End Sub

    Private Sub SaveToolStripButton_Click(ByVal sender As System.Object, ByVal e As System.EventArgs) Handles SaveToolStripButton.Click
        convertirdoc()
    End Sub
End Class
