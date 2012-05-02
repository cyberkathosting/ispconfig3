Imports Word = Microsoft.Office.Interop.Word
Imports Microsoft.Office.Interop
Imports System.Runtime.InteropServices


Public Class Form1





    Private Sub Form1_Load(ByVal sender As System.Object, ByVal e As System.EventArgs) Handles MyBase.Load




    End Sub

    Private Sub Button1_Click(ByVal sender As System.Object, ByVal e As System.EventArgs)

        Dim doc As New Word.Document
        Dim WordApp As New Word.Application()
        Dim file As Object = "Z:\j.doc"
        Dim Nothingobj As Object = System.Reflection.Missing.Value
        doc = WordApp.Documents.Open(file, Nothingobj, Nothingobj, Nothingobj, Nothingobj, Nothingobj, Nothingobj, Nothingobj, Nothingobj, Nothingobj, Nothingobj, Nothingobj)
        doc.ActiveWindow.Selection.WholeStory()
        doc.ActiveWindow.Selection.Copy()


        Dim data As IDataObject = Clipboard.GetDataObject()

        doc.Close()
    End Sub









    Private Sub DesarToolStripMenuItem_Click(ByVal sender As System.Object, ByVal e As System.EventArgs)
        Dim saveFileDialog1 As New SaveFileDialog()
        saveFileDialog1.Filter = "Document de Word Convertit|*.doc"
        saveFileDialog1.Title = "Guardar Document de Word Convertit"
        saveFileDialog1.ShowDialog()


        If saveFileDialog1.ShowDialog() = DialogResult.OK Then
            RichTextBox1.SaveFile(saveFileDialog1.FileName, _
            RichTextBoxStreamType.PlainText)
            MsgBox("correcto")
        End If
    End Sub















 

    Private Sub SortirToolStripMenuItem_Click(ByVal sender As System.Object, ByVal e As System.EventArgs)
        End
    End Sub

    


    Private Sub ConvertirDocumentToolStripMenuItem_Click(ByVal sender As System.Object, ByVal e As System.EventArgs) Handles ConvertirDocumentToolStripMenuItem.Click
        

    End Sub

    Private Sub ObrirDocumentToolStripMenuItem_Click(ByVal sender As System.Object, ByVal e As System.EventArgs) Handles ObrirDocumentToolStripMenuItem.Click
        RichTextBox1.Text = ""
        
    End Sub


    Private Sub GuardarToolStripMenuItem_Click(ByVal sender As System.Object, ByVal e As System.EventArgs) Handles GuardarToolStripMenuItem.Click

    End Sub

    Private Sub GenerarWordToolStripMenuItem_Click(ByVal sender As System.Object, ByVal e As System.EventArgs) Handles GenerarWordToolStripMenuItem.Click
        Dim saveFileDialog1 As New SaveFileDialog()
        saveFileDialog1.Filter = "Word Final |*.doc"
        saveFileDialog1.Title = "Document de Word Final "

        If saveFileDialog1.ShowDialog() = DialogResult.OK Then

            ' Create Word Application
            Dim oWord As Object
            oWord = CreateObject("Word.Application")
            ' Create new word document

            Dim doc As New Word.Document
            oWord.ScreenUpdating = False

            ToolStripStatusLabel1.Text = "Word obert"
            Dim file As Object = saveFileDialog1.FileName
            
            Dim data As IDataObject = Clipboard.GetDataObject()
            Dim oTable As Word.Table
            Dim datos(0 To RichTextBox1.Lines.GetLength(0), 0 To 2) As String
            Dim i As Integer
            i = 0
            Dim lines() As String = RichTextBox1.Lines
            Dim doit As String
            doit = "false"
            For Each line As String In RichTextBox1.Lines
                Dim primercaracter As String
                If RichTextBox1.Lines(i).ToString = vbCrLf Then MsgBox(RichTextBox1.Lines(i).ToString)
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
            oWord.Visible = False
            oTable = doc.Tables.Add(doc.Bookmarks.Item("\endofdoc").Range, datos2.GetUpperBound(0), 2)
            oTable.Range.ParagraphFormat.SpaceAfter = 6
            oTable.Range.Font.Name = "Times New Roman"
            oTable.Range.Font.Size = 11
            oTable.Range.Font.Bold = True

            Dim takes As Boolean = False
            For n As Integer = 1 To datos2.GetUpperBound(0) Step +1
                Me.Text = "Quedan " & (datos2.GetUpperBound(0) - n) - 1 & " Lineas per convertir"
                If (n <= 1) Then
                    oTable.Cell(1, 1).Range.Text = datos2(n - 1, 1)
                    oTable.Cell(1, 1).Merge(oTable.Cell(1, 2))
                Else
                    Dim pric As String
                    '(datos(n - 1, 1))
                    If (datos2(n - 1, 1) = "") Then
                        ' oTable.Rows(n - 1).Delete()
                    Else

                        If datos2(n - 1, 1) IsNot "" Then
                            pric = datos2(n - 1, 1).Substring(0, 1).ToString
                            If (pric = "<") Then
                                oTable.Cell(n, 1).Merge(oTable.Cell(n, 2))
                                oTable.Cell(n, 1).Range.Text = datos2(n - 1, 1)
                                oTable.Cell(n, 1).Range.Font.ColorIndex = Word.WdColorIndex.wdGreen
                                takes = True

                            Else
                                If takes = True Then
                                    oTable.Cell(n, 1).Range.Text = "*" & datos2(n - 1, 1) & "*"
                                    oTable.Cell(n, 2).Range.Text = datos2(n - 1, 2)
                                    oTable.Cell(n, 2).Width = 360
                                    oTable.Cell(n, 1).Width = 100
                                Else
                                    oTable.Cell(n, 1).Range.Text = datos2(n - 1, 1)
                                    oTable.Cell(n, 1).Merge(oTable.Cell(n, 2))

                                End If

                            End If
                        Else
                            'oTable.Rows(n - 1).Delete()
                        End If

                    End If
                End If

            Next
            doc.SaveAs2(saveFileDialog1.FileName)

            doc.Close()
            oWord.Application.Quit()
            MsgBox("Guardat :)")
            ToolStripStatusLabel1.Text = "Word tancat"
        End If
    End Sub

    Private Sub Button6_Click(ByVal sender As System.Object, ByVal e As System.EventArgs) Handles Button6.Click

    End Sub

    Private Sub Button9_Click(ByVal sender As System.Object, ByVal e As System.EventArgs) Handles Button9.Click

    End Sub
End Class