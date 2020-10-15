# `! lic=cc0`, con=<norkov@soletude.ca>`, rev=$Id$
# Used in Stagsi Cookbook: `@https://go.soletude.ca/stagsi/ex/HO`@

require 'rmagick'
require 'fileutils'

data_path = 'C:/Stagsi/Database'    # `! +REPLACEME='.*'

IO.read('strings.csv')              # `! +REPLACEME=Strings
    .scan(/^\s*(\d+)(\s*[,;]\s*|\s+)(\S.*)$/)
    .each { |a|
        rowid, delim, str = a
        # `! +REPLACEME=1000
        mask = "#{data_path}/#{(rowid.to_i/1000).floor()}/[cut]#{rowid}.*"

        files = Dir.glob(mask)
            .map { |f| [File.basename(f)[0], f] }
            .to_h

        if files['c'] then
            file = files['c']
        elsif files['u'] then
            file = files['u']
        elsif files['t']
            dir, file = File.split(files['t'])
            file = "#{dir}/" + file.sub(/./, 'u')
            FileUtils.copy files['t'], file, :verbose => true
        else
            next
        end

        im = Magick::Image.read(file).first
        text = Magick::Draw.new

        # Actual overlaying happens here.
        text.annotate(im, 0, 0, 0, 0, str) {
            self.gravity = Magick::SouthGravity
            self.pointsize = 50
            self.fill = 'black'
            self.font_weight = Magick::BoldWeight
            self.stroke = 'white'
            self.stroke_width = 3
        }

        im.write(file)
        im.destroy!
    }
