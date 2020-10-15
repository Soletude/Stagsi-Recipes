// `! lic=cc0`, con=<norkov@soletude.ca>`, rev=$Id$
// Used in Stagsi Cookbook: `@https://go.soletude.ca/stagsi/ex/GP`@

using System.Globalization;
using System.IO;
using System.Text;
using System.Windows.Media.Imaging;
using System.Windows.Media;
using System.Windows;

namespace Soletude.Stagsi.Plugins
{
    public class Txt
    {
        private const string TxtFormatName = "txt";
        private const string EndLineChars = "\r\n";
        // `! +REPLACEME=".*"
        private const string RenderFontName = "Arial";
        private const int MaxLength = 10000;
        private const double DefaultSize = 200;
        private static readonly string[] InvalidStrings = {"\0"};

        public static void StagsiPlugin(IPluginService service)
        {
            service.RegisterImageLoaderV1(RenderTxt, new ImageLoaderV1 { FileExtensions = new[] { TxtFormatName }});
        }

        private static BitmapSource RenderTxt(Stream input, IImageLoadingV1 parameters)
        {
            var len = input.Length;
            if (input.Length > MaxLength)
            {
                len = MaxLength;
            }
            byte[] buf = new byte[len];
            input.Read(buf, 0, buf.Length);
            string allText = Encoding.UTF8.GetString(buf);

            foreach (var v in InvalidStrings)
            {
                if (allText.Contains(v))
                {
                    // Binary data - skip.
                    return null;
                }
            }

            allText = allText.Replace(EndLineChars, " ");
            // `! +REPLACEME=[\d.]+?|Black
            var txt = new FormattedText(allText, CultureInfo.CurrentCulture, FlowDirection.LeftToRight, new Typeface(RenderFontName), 10.0, Brushes.Black);
            return WriteTextToImage(txt, new Point(0, 0),  parameters);
        }

        public static BitmapSource WriteTextToImage(FormattedText text, Point position, IImageLoadingV1 parameters)
        {
            var wid = parameters.Width;
            if (wid == 0 || double.IsNaN(wid))
            {
                wid = DefaultSize;
            }

            var hei = parameters.Height;
            if (hei == 0 || double.IsNaN(hei))
            {
                hei = DefaultSize;
            }

            var visual = new DrawingVisual();
            var prop = hei / wid;
            hei = (int) (wid * prop);

            text.MaxTextWidth = wid;
            using (var dc = visual.RenderOpen())
            {
                dc.DrawRectangle(Brushes.White, null, new Rect(new Size(wid, hei)));
                dc.DrawText(text, position);
            }

            var target = new RenderTargetBitmap((int) wid, (int) hei, 96.0, 96.0, PixelFormats.Default);
            target.Render(visual);

            parameters.Format = TxtFormatName;

            return target;
        }
    }
}