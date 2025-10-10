# Test Fixtures

This directory contains minimal test files used for integration testing of the Laravel AI Assistant package.

## Files

### test-audio.mp3
- **Size**: 483 bytes
- **Format**: MP3 audio with ID3 v2.3 tags
- **Purpose**: Used for testing audio transcription and translation adapters
- **Content**: Minimal valid MP3 file with ID3 metadata

### test-image.png
- **Size**: 70 bytes
- **Format**: PNG image (1x1 pixel, RGBA)
- **Purpose**: Used for testing image edit and variation adapters
- **Content**: Single red pixel image

## Usage

These fixtures are used in both simulated and real API integration tests:

1. **Simulated Tests**: Tests that verify adapter transformations without making actual API calls
2. **Real API Tests**: Tests marked with `@group integration` that make actual OpenAI API calls

## Cost Optimization

These files are intentionally kept minimal to:
- Reduce API costs during integration testing
- Speed up test execution
- Minimize bandwidth usage

The small file sizes ensure that the complete integration test suite costs less than $1 to run, as specified in the acceptance criteria.
