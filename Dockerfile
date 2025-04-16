# Use the official PHP image
FROM php:8.2-cli

# Set the working directory
WORKDIR /app

# Copy your PHP files into the container
COPY . .

# Expose the port Render expects (10000)
EXPOSE 10000

# Start the PHP built-in server
CMD ["php", "-S", "0.0.0.0:10000"]
