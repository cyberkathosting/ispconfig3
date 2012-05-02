Public Class Form2

    Private Sub Form2_Load(ByVal sender As System.Object, ByVal e As System.EventArgs) Handles MyBase.Load

    End Sub

    Private Sub Button5_Click(ByVal sender As System.Object, ByVal e As System.EventArgs) Handles Button5.Click
        RichTextBox2.Text = "( ON )"
    End Sub



    Private Sub RichTextBox2_Click(ByVal sender As Object, ByVal e As System.EventArgs) Handles RichTextBox2.Click
        RichTextBox2.SelectAll()
    End Sub


    Private Sub Button7_Click_1(ByVal sender As System.Object, ByVal e As System.EventArgs) Handles Button7.Click
        RichTextBox2.Text = "( OFF )"
    End Sub

    Private Sub Button6_Click_1(ByVal sender As System.Object, ByVal e As System.EventArgs) Handles Button6.Click
        RichTextBox2.Text = "( ON / OFF )"
    End Sub

    Private Sub Button9_Click_1(ByVal sender As System.Object, ByVal e As System.EventArgs) Handles Button9.Click
        If TextBox1.Text IsNot "" Then
            RichTextBox2.Text = TextBox1.Text
        End If
    End Sub
End Class